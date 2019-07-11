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
        $postFilter = $this->getPostFilters();
        $hasPostFilter = $postFilter != null;

        return array_filter([
            'query' => $this->getQuery(),
            'post_filter' => $postFilter,
            'aggs' => $this->getAggs($hasPostFilter),
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

    private function getPostFilters(Filter $exclude = null): ?array
    {
        $postFilters = [];

        foreach ($this->search->getFilters() as $filter) {
            if ($filter->isActive() && $filter->isPostFilter() && $filter !== $exclude) {
                $postFilters[] = $filter->getQuery();
            }
        }

        return $postFilters ? ['bool' => ['must' => $postFilters]] : null;
    }

    private function getAggs($hasPostFilter = false): ?array
    {
        $aggs = [];

        foreach ($this->search->getQueryFacets() as $facet => $size) {
            $aggs[$facet] = ['terms' => ['field' => $facet, 'size' => $size]];
        }

        foreach ($this->search->getFilters() as $filter) {
            if (!$filter->hasAggSize()) {
                continue;
            }

            $aggs[$filter->getName()] = $hasPostFilter ? $this->getAggPostFilter($filter) : $this->getAgg($filter);
        }

        return array_filter($aggs);
    }

    private function getAgg(Filter $filter): ?array
    {
        $agg = ['terms' => ['field' => $filter->getField(), 'size' => $filter->getAggSize()]];

        if ($filter->getSortField() !== null) {
            $agg['terms']['order'] = [$filter->getSortField() => $filter->getSortOrder(),];
        }

        return $agg;
    }

    /**
     * If the search uses post filtering, we need to filter other post filter aggregation
     */
    private function getAggPostFilter(Filter $filter)
    {
        $agg = $this->getAgg($filter);
        $aggFilter = $this->getPostFilters($filter);

        if (null === $aggFilter) {
            return $agg;
        }

        return [
            'filter' => $aggFilter,
            'aggs' => ['filtered_'.$filter->getName() => $agg]
        ];
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
