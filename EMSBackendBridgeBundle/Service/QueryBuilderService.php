<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service;




use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity\AnalyserSet;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity\SearchValue;

class QueryBuilderService{
    
    /**
     * 
     */
    public function __construct()
    {
    }
    
    
    
    /**
     * @param string $query
     *
     * @return SearchValue[]
     */
    private function createSearchValues($tokens)
    {
        $searchValues = [];
        
        foreach ($tokens as $token) {
            $searchValues[$token] = new SearchValue($token);
        }
        
        return $searchValues;
    }
    
    
    
    /**
     * @param SearchValue $searchValue
     * @param AnalyserSet $analyzer
     */
    private function addSynonyms(SearchValue &$searchValue, AnalyserSet $analyzer, $analyzerField)
    {
        $query = [
            'bool' => [
                'must' => $searchValue->getQuery($analyzer->getSynonymsSearchField(), $analyzerField)
            ]
        ];
        
        if ($analyzer->getSynonymsFilter()){
            $query['bool']['must'] = [$query['bool']['must'], ['bool' => $analyzer->getSynonymsFilter()]];
        }
        
        $documents = $analyzer->getClientRequest()->search($analyzer->getSynonymTypes(), [
            '_source' => false,
            'query' => $query,
        ], 0, 20);
        
        if($documents['hits']['total'] <= 20){
            foreach ($documents['hits']['hits'] as $document) {
                $searchValue->addSynonym($document);
            }            
        }
    }
    
    
    
    /**
     * @param array  $searchValues
     * @param AnalyserSet $analyzer
     *
     * @return array
     */
    private function createBodyPerAnalyzer(array $searchValues, AnalyserSet $analyzer, $analyzerField)
    {
        
        $filter = $analyzer->getFilter();
        
        if(empty($filter) || !isset($filter['bool'])) {
            $filter['bool'] = [
            ];
        }
        
        if(!isset($filter['bool']['must'])) {
            $filter['bool']['must'] = [
            ];
        }
        

        /**@var SearchValue $searchValue*/
        foreach ($searchValues as $searchValue) {
            $partialQuery = $searchValue->makeShould($analyzer->getSearchField(), $analyzer->getSearchSynonymsInField(), $analyzerField);
            
            $combinedQuery = $this->combineBools($filter['bool']['must'], $partialQuery);
            
            if (empty($filter['bool']['must'])) {
                $filter['bool']['must'][] = $combinedQuery;
            } else {
                $filter['bool']['must'] = $combinedQuery;
            }
            
            
        }

        return $filter;
    }
    
    /**
     * Bool, Must, Should keywords are unique per level of the json hierarchy.
     * This function makes sure that multiple should and must clauses are combined into one clause (in stead of added and ignored).
     * 
     * @param array $query
     * @param array $bool
     * @return array
     * @throws \Exception
     */
    private function combineBools($query, $bool)
    {
        if (!isset($bool['bool'])) {
            throw new \Exception("bool parameter to combine with query does not contain a bool array.");
        }

        if (sizeof($query) > 1) {
            throw new \Exception("malformed query, send an array with only one bool constraint at a time.");
        }
        
        if (empty($query)) {
            return $bool;
        }
        
        $shouldOrMustKey = key($bool['bool']);

        if (!isset($query[0]['bool'][$shouldOrMustKey])) {
            $query[0]['bool'] = $bool['bool'];
            return $query;
        }
        
        foreach ($bool['bool'][$shouldOrMustKey] as $subquery) {
            $query[0]['bool'][$shouldOrMustKey][] = $subquery;
        }

        return $query;
    }
    
    private function buildPerAnalyzer($queryString, AnalyserSet $analyzerSet){
        
        $analyzer = $analyzerSet->getClientRequest()->getFieldAnalyzer($analyzerSet->getSearchField());
        $tokens = $analyzerSet->getClientRequest()->analyze($queryString, $analyzerSet->getSearchField());
        
        $searchValues = $this->createSearchValues($tokens);
        
        if(!empty($analyzerSet->getSynonymTypes())){            
            foreach ($searchValues as $searchValue) {
                $this->addSynonyms($searchValue, $analyzerSet, $analyzer);
            }
        }

        return $this->createBodyPerAnalyzer($searchValues, $analyzerSet, $analyzer);
    }
    
    
    
    public function getQuery($queryString, $analyzerSets){
        $should = [];
        if(!$queryString){
            /**@var AnalyserSet $analyzer*/
            foreach ($analyzerSets  as $analyzer) {
                $should[] = $analyzer->getFilter();
            }            
        } else {
            foreach ($analyzerSets  as $analyzer) {
                $should[] = $this->buildPerAnalyzer($queryString, $analyzer);
            }            
        }

        $out = [
            'bool' => [
                'should' => $should
            ]
        ];
        
        return $out;
    }
    
}