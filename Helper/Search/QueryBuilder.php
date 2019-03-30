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
                $should[] = $this->buildPerAnalyzer($search, $field);
            }
        } else {
            $should = $search->createFilter();
        }

        return ['bool' => ['should' => $should]];
    }

    private function createSearchValues(array $tokens): array
    {
        $searchValues = [];

        foreach ($tokens as $token) {
            $searchValues[$token] = new TextValue($token);
        }

        return $searchValues;
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

    private function createBodyPerAnalyzer(Search $search, array $searchValues, string $field, $analyzer)
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
        foreach ($searchValues as $searchValue) {
            $filter['bool']['must'][] = $searchValue->makeShould($field, $analyzer);
        }

        return $filter;
    }

    private function buildPerAnalyzer(Search $search, string $field)
    {
        $analyzer = $this->clientRequest->getFieldAnalyzer($field);
        $tokens = $this->clientRequest->analyze($search->getQueryString(), $field);

        $searchValues = $this->createSearchValues($tokens);

        if ($search->hasSynonyms()) {
            foreach ($searchValues as $searchValue) {
                $this->addSynonyms($search, $searchValue, $analyzer);
            }
        }

        return $this->createBodyPerAnalyzer($search, $searchValues, $field, $analyzer);
    }
}
