<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity;

/**
 * If we search for 'foo bar' 
 * the SearchManager will create two SearchValue instances
 */
class SearchValue
{
    /**
     * @var string
     */
    private $term;
    
    /**
     * @var array
     */
    private $synonyms;
    
    /**
     * @param string $term
     */
    public function __construct($term)
    {
        $this->term = $term;
        $this->synonyms = [];
    }
    
    /**
     * @param array $document
     */
    public function addSynonym(array $document)
    {
        $this->synonyms[] = sprintf('%s %s', $document['_type'], $document['_id']);
    }
    
    /**
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
    }
    
    /**
     * @param string $field
     *
     * @return [][]
     */
    public function makeShould($searchFields, $synonymsSearchField)
    {

        $should = [];
        $should[] = [
            'match' => [
                $searchFields => $this->getTerm(),
            ]
        ];
        
        foreach ($this->synonyms as $emsLink) {
            if(!empty($emsLink)){
                $should[] = $this->makeQuery($synonymsSearchField, $emsLink);                
            }
        }
        
        return ['bool' => [
            'should' => $should,
        ]];
    }
    
    /**
     * @param string $field
     * @param string $query
     *
     * @return string
     */
    private function makeQuery($field, $query)
    {
        return [
            'query_string' => [
                'default_field' => $field,
                'query' => $query,
                'default_operator' => 'AND',
//                 $field => $query,
            ]
        ];
    }
}
