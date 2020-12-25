<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\Filter as FilterAggregation;
use Elastica\Aggregation\Nested as NestedAggregation;
use Elastica\Aggregation\ReverseNested;
use Elastica\Aggregation\Terms as TermsAggregation;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Nested;
use Elastica\Query\Terms;
use Elastica\Suggest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\CommonBundle\Search\Search as CommonSearch;

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

    /**
     * @param string[] $types
     */
    public function buildSearch(array $types): CommonSearch
    {
        $query = $this->getQuery();
        $search = $this->clientRequest->initializeCommonSearch($types, $query);
        $search->setPostFilter($this->getPostFilters());
        $hasPostFilter = (null !== $search->getPostFilter());
        foreach ($this->getAggs($hasPostFilter) as $aggregation) {
            $search->addAggregation($aggregation);
        }
        $search->setSort($this->getSort());
        $suggest = $this->getSuggest();
        if (null !== $suggest) {
            $search->setSuggest($suggest);
        }
        $search->setHighlight($this->search->getHighlight());

        return $search;
    }

    private function getQuery(): ?AbstractQuery
    {
        if ($this->search->hasQueryString()) {
            return $this->getQueryWithString($this->search->getQueryString());
        }

        return $this->getQueryFilters();
    }

    private function getQueryWithString(string $queryString): ?AbstractQuery
    {
        $query = new BoolQuery();
        $filterMust = $this->getQueryFilters();
        if (null === $filterMust) {
            $filterMust = new BoolQuery();
        }

        $analyzer = new Analyzer($this->clientRequest);
        $tokens = $this->clientRequest->analyze($queryString, $this->search->getAnalyzer());

        foreach ($this->search->getFields() as $field) {
            $textValues = $analyzer->getTextValues($field, $this->search->getAnalyzer(), $tokens, $this->search->getSynonyms());
            if (0 === \count($textValues)) {
                continue;
            }

            $textMust = clone $filterMust;
            foreach ($textValues as $textValue) {
                $textMust->addMust($textValue->makeShould());
            }
            $query->addShould($textMust);
        }

        if (0 === $query->count()) {
            return null;
        }

        return $query;
    }

    public function getQueryFilters(): ?BoolQuery
    {
        $query = new BoolQuery();

        foreach ($this->search->getQueryFacets() as $field => $terms) {
            $query->addMust(new Terms($field, $terms));
        }

        foreach ($this->search->getFilters() as $filter) {
            if (!$filter->isActive() || $filter->isPostFilter()) {
                continue;
            }
            $queryFilter = $filter->getQuery();
            if (null === $queryFilter) {
                continue;
            }

            $nestedPath = $filter->getNestedPath();
            if (null !== $nestedPath) {
                $nested = new Nested();
                $nested->setPath($nestedPath);
                $nested->setQuery($queryFilter);
                $nested->setParam('ignore_unmapped', true);
                $query->addMust($nested);
            } else {
                $query->addMust($queryFilter);
            }
        }

        if (0 === $query->count()) {
            return null;
        }

        return $query;
    }

    private function getPostFilters(Filter $exclude = null, string $nestedPath = null): ?AbstractQuery
    {
        $postFilters = new BoolQuery();

        foreach ($this->search->getFilters() as $filter) {
            if (!$filter->isActive() || !$filter->isPostFilter() || $filter === $exclude) {
                continue;
            }
            $query = $filter->getQuery();
            if (null === $query) {
                continue;
            }

            $filterNestedPath = $filter->getNestedPath();
            if (null !== $filterNestedPath && $filterNestedPath !== $nestedPath) {
                $nested = new Nested();
                $nested->setPath($filterNestedPath);
                $nested->setQuery($query);
                $nested->setParam('ignore_unmapped', true);
                $nestedQueries[$filter->getNestedPath()]['bool']['must'][] = $filter->getQuery();
                $postFilters->addMust($nestedQueries);
            } else {
                $postFilters->addMust($query);
            }
        }

        if (0 === \count($postFilters)) {
            return null;
        }

        return $postFilters;
    }

    /**
     * @return AbstractAggregation[]
     */
    private function getAggs(bool $hasPostFilter = false): array
    {
        $aggs = [];

        foreach ($this->search->getQueryFacets() as $facet => $size) {
            $terms = new TermsAggregation($facet);
            $terms->setField($facet);
            $terms->setSize($size);
            $aggs[$facet] = $terms;
        }

        foreach ($this->search->getFilters() as $filter) {
            if (!$filter->hasAggSize()) {
                continue;
            }

            $aggregation = $hasPostFilter ? $this->getAggPostFilter($filter) : $this->getAgg($filter);

            $nestedPath = $filter->getNestedPath();
            if (null !== $nestedPath) {
                $nested = new NestedAggregation($filter->getName(), $nestedPath);
                $nested->addAggregation($aggregation);
            }

            $aggs[$filter->getName()] = $aggregation;
        }

        return \array_filter($aggs);
    }

    private function getAgg(Filter $filter): AbstractAggregation
    {
        $agg = new TermsAggregation($filter->getName());
        $agg->setField($filter->getField());
        $aggSize = $filter->getAggSize();
        if (null !== $aggSize) {
            $agg->setSize($aggSize);
        }

        if ($filter->isReversedNested()) {
            $subAggregation = new ReverseNested('reversed_nested');
            $agg->addAggregation($subAggregation);
        }

        $orderField = $filter->getSortField();
        if (null !== $orderField) {
            $agg->setOrder($orderField, $filter->getSortOrder());
        }

        return $agg;
    }

    /**
     * If the search uses post filtering, we need to filter other post filter aggregation.
     */
    private function getAggPostFilter(Filter $filter): AbstractAggregation
    {
        $agg = $this->getAgg($filter);
        $postFilters = $this->getPostFilters($filter, $filter->getNestedPath());

        if (null === $postFilters) {
            return $agg;
        }
        $filterAggregation = new FilterAggregation($filter->getName());
        $filterAggregation->setFilter($postFilters);

        $agg->setName('filtered_'.$filter->getName());
        $filterAggregation->addAggregation($agg);

        return $filterAggregation;
    }

    private function getSuggest(): ?Suggest
    {
        $queryString = $this->search->getQueryString();
        if (null === $queryString) {
            return null;
        }

        $suggest = new Suggest();
        foreach ($this->search->getSuggestFields() as $field) {
            $term = new Suggest\Term('suggest-'.$field, $field);
            $term->setText($queryString);
            $suggest->addSuggestion($term);
        }

        return $suggest;
    }

    /**
     * @return array<mixed>
     */
    private function getSort(): array
    {
        if (null === $sort = $this->search->getSort()) {
            return $this->buildSort($this->search->getDefaultSorts());
        }

        return $this->buildSort([$sort]);
    }

    /**
     * @param array<mixed> $searchSorts
     *
     * @return array<string, mixed>
     */
    private function buildSort(array $searchSorts): array
    {
        $sorts = [];

        foreach ($searchSorts as $sort) {
            $field = $sort['field'] ?? null;
            if (!\is_string($field)) {
                throw new \RuntimeException('Unexpected not named search sort');
            }

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
