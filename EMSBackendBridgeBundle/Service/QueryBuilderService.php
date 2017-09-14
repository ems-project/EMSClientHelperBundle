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
    private function addSynonyms(SearchValue &$searchValue, AnalyserSet $analyzer)
    {
        $query = [
            'bool' => [
                'must' => [
                    'query_string' => [
                        'default_field' => $analyzer->getSynonymsSearchField(),
                        'query' => $searchValue->getTerm().'*',
                    ]
                ]
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
    private function createBodyPerAnalyzer(array $searchValues, AnalyserSet $analyzer)
    {

        $must = [];
        
        /**@var SearchValue $searchValue*/
        foreach ($searchValues as $searchValue) {
            $must[] = $searchValue->makeShould($analyzer->getSearchField(), $analyzer->getSearchSynonymsInField());//TODO: get serach field for analyzer set
            
        }
        
        return ['bool' => [
            'must' => $must,
        ]];
    }
    
    private function buildPerAnalyzer($queryString, AnalyserSet $analyzer){
        
        
        $tokens = $analyzer->getClientRequest()->analyze($queryString, $analyzer->getSearchField());
        
        $searchValues = $this->createSearchValues($tokens);
        
        
        
        foreach ($searchValues as $searchValue) {
            $this->addSynonyms($searchValue, $analyzer);
        }
        dump($searchValues);
        
        return $this->createBodyPerAnalyzer($searchValues, $analyzer);
    }
    
    
    
    public function getQuery($queryString, $analyzerSets){
        
        
        
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