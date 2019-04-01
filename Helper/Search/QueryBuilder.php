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
        $should = [];

        if ($this->search->hasQueryString()) {
            foreach ($this->search->getFields() as $field) {
                $textValues = $this->createTextValues($field);

                $should[] = $this->createBodyPerAnalyzer($textValues);
            }
        } else {
            $should = $this->search->createFilter();
        }

        return ['bool' => ['should' => $should]];
    }

    private function addSynonyms(TextValue $textValue)
    {
        foreach ($this->search->getSynonyms() as $synonym) {
            $queryText = $textValue->getQuery($synonym->getSearchField(), $textValue->getAnalyzer());
            $querySynonym = $synonym->getQuery($queryText);

            $documents = $this->clientRequest->search([], ['_source' => ['_contenttype'], 'query' => $querySynonym], 0, 20);

            if ($documents['hits']['total'] > 20) {
                continue;
            }

            foreach ($documents['hits']['hits'] as $doc) {
                $textValue->addSynonym($synonym->getField(), $doc);
            }
        }
    }

    private function createBodyPerAnalyzer(array $textValues)
    {
        $filter = $this->search->createFilter();

        if (empty($filter) || !isset($filter['bool'])) {
            $filter['bool'] = [
            ];
        }

        if (!isset($filter['bool']['must'])) {
            $filter['bool']['must'] = [
            ];
        }

        /**@var TextValue $searchValue */
        foreach ($textValues as $searchValue) {
            $filter['bool']['must'][] = $searchValue->makeShould();
        }

        return $filter;
    }

    private function createTextValues(string $field): array
    {
        $analyzer = $this->clientRequest->getFieldAnalyzer($field);
        $tokens = $this->clientRequest->analyze($this->search->getQueryString(), $field);

        $textValues = [];

        foreach ($tokens as $token) {
            $textValue = new TextValue($token, $field, $analyzer);

            if ($this->search->hasSynonyms()) {
                $this->addSynonyms($textValue);
            }

            $textValues[$token] = $textValue;
        }

        return $textValues;
    }

    private function getSuggest(): ?array
    {
        if (!$this->search->hasQueryString()) {
            return null;
        }

        $suggest = [];

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
