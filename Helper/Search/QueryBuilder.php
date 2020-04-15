<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Search\Filter\Filter;

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
            'highlight' => $this->search->getHighlight()
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

    public function getQueryFilters(): array
    {
        $query = [];
        $nestedQueries = [];

        foreach ($this->search->getQueryFacets() as $field => $terms) {
            $query['bool']['must'][] = ['terms' => [$field => $terms]];
        }

        foreach ($this->search->getFilters() as $filter) {
            if (!$filter->isActive() || $filter->isPostFilter()) {
                continue;
            }

            if ($filter->getOptions()->hasNestedPath()) {
                $nestedQueries[$filter->getOptions()->getNestedPath()]['bool']['must'][] = $filter->getQuery();
            } else {
                $query['bool']['must'][] = $filter->getQuery();
            }
        }

        foreach ($nestedQueries as $path => $queries) {
            $query['bool']['must'][] = ['nested' => [
                'path' => $path,
                'ignore_unmapped' => true,
                'query' => $queries
            ]];
        }

        return $query;
    }

    private function getPostFilters(Filter $exclude = null, $nestedPath = null): ?array
    {
        $postFilters = [];
        $nestedQueries = [];

        foreach ($this->search->getFilters() as $filter) {
            if (!$filter->isActive() || !$filter->isPostFilter() || $filter === $exclude) {
                continue;
            }

            if ($filter->getOptions()->hasNestedPath() && $filter->getOptions()->getNestedPath() !== $nestedPath) {
                $nestedQueries[$filter->getOptions()->getNestedPath()]['bool']['must'][] = $filter->getQuery();
            } else {
                $postFilters[] = $filter->getQuery();
            }
        }

        foreach ($nestedQueries as $path => $queries) {
            $postFilters[] = ['nested' => [
                'path' => $path,
                'ignore_unmapped' => true,
                'query' => $queries
            ]];
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
            if (!$filter->getOptions()->hasAggSize()) {
                continue;
            }

            $aggregation =  $hasPostFilter ? $this->getAggPostFilter($filter) : $this->getAgg($filter);

            if ($filter->getOptions()->hasNestedPath()) {
                $aggregation = [
                    'nested' => ['path' => $filter->getOptions()->getNestedPath()],
                    'aggs' => ['nested' => $aggregation]
                ];
            }

            $aggs[$filter->getName()] = $aggregation;
        }

        return array_filter($aggs);
    }

    private function getAgg(Filter $filter): ?array
    {
        $agg = ['terms' => ['field' => $filter->getField(), 'size' => $filter->getOptions()->getAggSize()]];

        if ($filter->getOptions()->isReversedNested()) {
            $agg = array_merge($agg, ['aggs' => [ 'reversed_nested' => [ 'reverse_nested' => new \stdClass() ]]]);
        }

        if ($filter->getOptions()->hasSortField()) {
            $agg['terms']['order'] = [$filter->getOptions()->getSortField() => $filter->getOptions()->getSortOrder()];
        }

        return $agg;
    }

    /**
     * If the search uses post filtering, we need to filter other post filter aggregation
     */
    private function getAggPostFilter(Filter $filter)
    {
        $agg = $this->getAgg($filter);
        $aggFilter = $this->getPostFilters($filter, $filter->getOptions()->getNestedPath());

        if (null === $aggFilter) {
            return $agg;
        }

        return [
            'filter' => $aggFilter,
            'aggs' => ['filtered_' . $filter->getName() => $agg]
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
        if (null === $sort = $this->search->getSort()) {
            return null;
        }

        $field = $sort['field'];
        unset($sort['field']);

        if (!isset($sort['order'])) {
            $sort['order'] = $this->search->getSortOrder();
        }

        return [$field => $sort];
    }
}
