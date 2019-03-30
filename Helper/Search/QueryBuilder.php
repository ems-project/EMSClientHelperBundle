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

    private function createSearchValues(array $tokens): array
    {
        $searchValues = [];

        foreach ($tokens as $token) {
            $searchValues[$token] = new TextValue($token);
        }

        return $searchValues;
    }

    private function addSynonyms(TextValue &$searchValue, AnalyserSet $analyzer, $analyzerField)
    {
        $query = [
            'bool' => [
                'must' => $searchValue->getQuery($analyzer->getSynonymsSearchField(), $analyzerField)
            ]
        ];

        if ($analyzer->getSynonymsFilter()) {
            $query['bool']['must'] = [$query['bool']['must'], ['bool' => $analyzer->getSynonymsFilter()]];
        }

        $documents = $this->clientRequest->search($analyzer->getSynonymTypes(), [
            '_source' => false,
            'query' => $query,
        ], 0, 20);

        if ($documents['hits']['total'] <= 20) {
            foreach ($documents['hits']['hits'] as $document) {
                $searchValue->addSynonym($document);
            }
        }
    }

    private function createBodyPerAnalyzer(array $searchValues, AnalyserSet $analyzer, $analyzerField)
    {
        $filter = $analyzer->getFilter();

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
            $filter['bool']['must'][] = $searchValue->makeShould($analyzer->getField(), $analyzer->getSearchSynonymsInField(), $analyzerField, $analyzer->getBoost());
        }

        return $filter;
    }

    private function buildPerAnalyzer($queryString, AnalyserSet $analyzerSet)
    {
        $analyzer = $this->clientRequest->getFieldAnalyzer($analyzerSet->getField());
        $tokens = $this->clientRequest->analyze($queryString, $analyzerSet->getField());

        $searchValues = $this->createSearchValues($tokens);

        if (!empty($analyzerSet->getSynonymTypes())) {
            foreach ($searchValues as $searchValue) {
                $this->addSynonyms($searchValue, $analyzerSet, $analyzer);
            }
        }

        return $this->createBodyPerAnalyzer($searchValues, $analyzerSet, $analyzer);
    }

    public function getQuery($queryString, $analyzerSets)
    {
        $should = [];
        if (!$queryString) {
            /**@var AnalyserSet $analyzer */
            foreach ($analyzerSets as $analyzer) {
                $filter = $analyzer->getFilter();
                if ($filter) {
                    $should[] = $filter;
                }
            }
        } else {
            foreach ($analyzerSets as $analyzer) {
                $should[] = $this->buildPerAnalyzer($queryString, $analyzer);
            }
        }

        $out = [
            'bool' => [
                'should' => $should
            ]
        ];

        //add aggs per facet index
        //add a must terms if there is at least one facets

        return $out;
    }
}
