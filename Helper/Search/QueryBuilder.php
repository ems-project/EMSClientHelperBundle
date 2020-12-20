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
        $hasPostFilter = null != $postFilter;

        return \array_filter([
            'query' => $this->getQuery(),
            'post_filter' => $postFilter,
            'aggs' => $this->getAggs($hasPostFilter),
            'suggest' => $this->getSuggest(),
            'sort' => $this->getSort(),
            'highlight' => $this->search->getHighlight(),
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

            if ($filter->isNested()) {
                $nestedQueries[$filter->getNestedPath()]['bool']['must'][] = $filter->getQuery();
            } else {
                $query['bool']['must'][] = $filter->getQuery();
            }
        }

        foreach ($nestedQueries as $path => $queries) {
            $query['bool']['must'][] = ['nested' => [
                'path' => $path,
                'ignore_unmapped' => true,
                'query' => $queries,
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

            if ($filter->isNested() && $filter->getNestedPath() !== $nestedPath) {
                $nestedQueries[$filter->getNestedPath()]['bool']['must'][] = $filter->getQuery();
            } else {
                $postFilters[] = $filter->getQuery();
            }
        }

        foreach ($nestedQueries as $path => $queries) {
            $postFilters[] = ['nested' => [
                'path' => $path,
                'ignore_unmapped' => true,
                'query' => $queries,
            ]];
        }

        if (0 === \count($postFilters)) {
            return null;
        }

        return $postFilters;
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

            $aggregation = $hasPostFilter ? $this->getAggPostFilter($filter) : $this->getAgg($filter);

            if ($filter->isNested()) {
                $aggregation = [
                    'nested' => ['path' => $filter->getNestedPath()],
                    'aggs' => ['nested' => $aggregation],
                ];
            }

            $aggs[$filter->getName()] = $aggregation;
        }

        return \array_filter($aggs);
    }

    private function getAgg(Filter $filter): ?array
    {
        $agg = ['terms' => ['field' => $filter->getField(), 'size' => $filter->getAggSize()]];

        if ($filter->isReversedNested()) {
            $agg = \array_merge($agg, ['aggs' => ['reversed_nested' => ['reverse_nested' => new \stdClass()]]]);
        }

        if (null !== $filter->getSortField()) {
            $agg['terms']['order'] = [$filter->getSortField() => $filter->getSortOrder()];
        }

        return $agg;
    }

    /**
     * If the search uses post filtering, we need to filter other post filter aggregation.
     */
    private function getAggPostFilter(Filter $filter)
    {
        $agg = $this->getAgg($filter);
        $postFilters = $this->getPostFilters($filter, $filter->getNestedPath());

        if (null === $postFilters) {
            return $agg;
        }

        return [
            'filter' => ['bool' => ['must' => $postFilters]],
            'aggs' => ['filtered_'.$filter->getName() => $agg],
        ];
    }

    private function getSuggest(): ?array
    {
        if (!$this->search->hasQueryString()) {
            return null;
        }

        $suggest = ['text' => $this->search->getQueryString()];

        foreach ($this->search->getSuggestFields() as $field) {
            $suggest['suggest-'.$field] = ['term' => ['field' => $field]];
        }

        return $suggest;
    }

    private function getSort(): array
    {
        if (null === $sort = $this->search->getSort()) {
            return $this->buildSort($this->search->getDefaultSorts());
        }

        return $this->buildSort([$sort]);
    }

    private function buildSort(array $searchSorts): array
    {
        $sorts = [];

        foreach ($searchSorts as $sort) {
            $field = $sort['field'];
            $includeScore = $sort['score'] ?? false;

            unset($sort['field'], $sort['score']);
            $sorts[$field] = $sort;

            if ($includeScore) {
                $sorts['_score'] = ['order' => 'desc'];
            }
        }

        return $sorts;
    }
}
