<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;

class QueryBuilder
{
    /** @var ClientRequest */
    private $clientRequest;

    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    public function buildQuery(Search $search): array
    {
        $should = [];

        if ($search->hasQueryString()) {
            foreach ($search->getFields() as $field) {
                $textValues = $this->createTextValues($search, $field);
                
                $should[] = $this->createBodyPerAnalyzer($search, $textValues);
            }
        } else {
            $should = $search->createFilter();
        }

        return ['bool' => ['should' => $should]];
    }


    private function addSynonyms(Search $search, TextValue $searchValue, string $analyzer)
    {
        foreach ($search->getSynonyms() as $synonym) {
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

    private function createBodyPerAnalyzer(Search $search, array $textValues)
    {
        $filter = $search->createFilter();

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

    private function createTextValues(Search $search, string $field): array
    {
        $analyzer = $this->clientRequest->getFieldAnalyzer($field);
        $tokens = $this->clientRequest->analyze($search->getQueryString(), $field);

        $textValues = [];

        foreach ($tokens as $token) {
            $textValue = new TextValue($token, $field, $analyzer);

            if ($search->hasSynonyms()) {
                $this->addSynonyms($search, $textValue, $analyzer);
            }

            $textValues[$token] = $textValue;
        }

        return $textValues;
    }
}
