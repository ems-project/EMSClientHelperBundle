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
            $filter['bool']['must'][] = $searchValue->makeShould($analyzer->getSearchField(), $analyzer->getSearchSynonymsInField(), $analyzerField);
            
        }
        
        return $filter;
    }
    
    private function buildPerAnalyzer($queryString, AnalyserSet $analyzerSet){
        
        $analyzer = $analyzerSet->getClientRequest()->getFieldAnalyzer($analyzerSet->getSearchField());
        $tokens = $analyzerSet->getClientRequest()->analyze($queryString, $analyzerSet->getSearchField());
        
        $searchValues = $this->createSearchValues($tokens);
        
        foreach ($searchValues as $searchValue) {
            $this->addSynonyms($searchValue, $analyzerSet, $analyzer);
        }

        return $this->createBodyPerAnalyzer($searchValues, $analyzerSet, $analyzer);
    }
    
    
    
    public function getQuery($queryString, $analyzerSets){
        
        if(!$queryString){
            return [];
        }
        
        $should = [];
        foreach ($analyzerSets  as $analyzer) {
            $should[] = $this->buildPerAnalyzer($queryString, $analyzer);
        }
        
        
        $out = [
            'bool' => [
                'should' => $should
            ]
        ];
        
        return $out;
    }
    
}