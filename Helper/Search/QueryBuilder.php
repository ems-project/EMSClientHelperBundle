<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;

class QueryBuilder
{
    /** @var ClientRequest */
    private $clientRequest;
    /** @var search */
    private $search;

    public function __construct(ClientRequest $clientRequest, Search $search)
    {
        $this->clientRequest = $clientRequest;
        $this->search = $search;
    }

    public function buildQuery(): array
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


    private function addSynonyms(TextValue $searchValue, string $analyzer)
    {
        foreach ($this->search->getSynonyms() as $synonym) {
            $queryText = $searchValue->getQuery($synonym->getSearchField(), $analyzer);
            $querySynonym = $synonym->getQuery($queryText);

            $documents = $this->clientRequest->search([], ['_source' => ['_contenttype'], 'query' => $querySynonym], 0, 20);

            if ($documents['hits']['total'] > 20) {
                continue;
            }

            foreach ($documents['hits']['hits'] as $doc) {
                $searchValue->addSynonym($synonym->getField(), $doc);
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

    private function createTextValues( string $field): array
    {
        $analyzer = $this->clientRequest->getFieldAnalyzer($field);
        $tokens = $this->clientRequest->analyze($this->search->getQueryString(), $field);

        $textValues = [];

        foreach ($tokens as $token) {
            $textValue = new TextValue($token, $field, $analyzer);

            if ($this->search->hasSynonyms()) {
                $this->addSynonyms($textValue, $analyzer);
            }

            $textValues[$token] = $textValue;
        }

        return $textValues;
    }
}
