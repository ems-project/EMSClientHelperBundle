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
            'aggs' => $this->search->getFacetsAggs(),
            'suggest' => $this->getSuggest(),
            'sort' => $this->getSort(),
        ]);
    }

    private function getQuery(): array
    {
        $query = [];
        $filterMust = $this->getQueryFilters();

        if ($this->search->hasQueryString()) {
            $analyzer = new Analyzer($this->clientRequest);

            foreach ($this->search->getFields() as $field) {
                $textValues = $analyzer->getTextValues($field, $this->search->getQueryString(), $this->search->getSynonyms());

                $textMust = [];
                foreach ($textValues as $textValue) {
                    $textMust['bool']['must'][] = $textValue->makeShould();
                }

                $query[] = \array_merge_recursive($filterMust, $textMust);
            }
        } else {
            $query = $filterMust;
        }

        return ['bool' => ['should' => $query]];
    }

    private function getQueryFilters(): array
    {
        $query = [];

        foreach ($this->search->getQueryFacets() as $field => $terms) {
            $query['bool']['must'][] = ['terms' => [$field => $terms]];
        }

        foreach ($this->search->getFilters() as $filter) {
            if ($filter->hasQuery()) {
                $query['bool']['must'][] = $filter->getQuery();
            }
        }

        return $query;
    }

    private function getSuggest(): ?array
    {
        if (!$this->search->hasQueryString()) {
            return null;
        }

        $suggest = ['text' => $this->search->getQueryString()];

        foreach ($this->search->getFields() as $field) {
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
                'unmapped_type' => 'long'
            ]
        ];
    }
}
