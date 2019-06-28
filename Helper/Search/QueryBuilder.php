<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;

class QueryBuilder
{
    /** @var ClientRequest */
    private $clientRequest;
    /** @var Search */
    private $search;

    public function __construct(ClientRequest $clientRequest, Search $search)
    {
        $this->clientRequest = $clientRequest;
        $this->search = $search;
    }

    public function buildBody(): array
    {
        return array_filter([
            'query' => $this->getQuery(),
            'post_filter' => $this->getPostFilters(),
            'aggs' => $this->getAggs(),
            'suggest' => $this->getSuggest(),
            'sort' => $this->getSort(),
        ]);
    }

    private function getQuery(): ?array
    {
        $filterMust = $this->getQueryFilters();

        if ($this->search->hasQueryString()) {
            return $this->getQueryWithString($this->search->getQueryString());
        } elseif ($filterMust) {
            return $filterMust;
        }

        return null;
    }

    private function getQueryWithString(string $queryString): array
    {
        $query = ['bool' => ['should' => []]];
        $filterMust = $this->getQueryFilters();

        $analyzer = new Analyzer($this->clientRequest);
        $tokens = $this->clientRequest->analyze($queryString, $this->search->getAnalyzer());

        foreach ($this->search->getFields() as $field) {
            $textValues = $analyzer->getTextValues($field, $this->search->getAnalyzer(), $tokens, $this->search->getSynonyms());

            $textMust = [];
            foreach ($textValues as $textValue) {
                $textMust['bool']['must'][] = $textValue->makeShould();
            }

            $query['bool']['should'][] = \array_merge_recursive($filterMust, $textMust);
        }

        return $query;
    }

    private function getQueryFilters(): array
    {
        $query = [];

        foreach ($this->search->getQueryFacets() as $field => $terms) {
            $query['bool']['must'][] = ['terms' => [$field => $terms]];
        }

        foreach ($this->search->getFilters() as $filter) {
            if ($filter->isActive() && !$filter->isPostFilter()) {
                $query['bool']['must'][] = $filter->getQuery();
            }
        }

        return $query;
    }

    private function getPostFilters(): array
    {
        $postFilters = [];

        foreach ($this->search->getFilters() as $filter) {
            if ($filter->isActive() && $filter->isPostFilter()) {
                $postFilters[] = $filter->getQuery();
            }
        }

        return $postFilters ? ['bool' => ['must' => $postFilters]] : [];
    }

    private function getAggs(): ?array
    {
        $aggs = [];

        foreach ($this->search->getQueryFacets() as $facet => $size) {
            $aggs[$facet] = ['terms' => ['field' => $facet, 'size' => $size]];
        }

        foreach ($this->search->getFilters() as $filter) {
            if ($filter->hasAggSize()) {
                $aggs[$filter->getName()] = ['terms' => ['field' => $filter->getField(), 'size' => $filter->getAggSize()]];
                if ($filter->getSortField() !== null) {
                    $aggs[$filter->getName()]['terms']['order'] = [
                        $filter->getSortField() => $filter->getSortOrder(),
                    ];
                }
            }
        }

        return $aggs;
    }

    private function getSuggest(): ?array
    {
        if (!$this->search->hasQueryString()) {
            return null;
        }

        $suggest = ['text' => $this->search->getQueryString()];

        foreach ($this->search->getSuggestFields() as $field) {
            $suggest['suggest-' . $field] = ['term' => ['field' => $field]];
        }

        return $suggest;
    }

    private function getSort(): ?array
    {
        if (!$this->search->getSortBy()) {
            return null;
        }

        return [
            $this->search->getSortBy() => [
                'order' => $this->search->getSortOrder(),
                'missing' => '_last',
            ]
        ];
    }
}
