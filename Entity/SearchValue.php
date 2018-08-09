<?php

namespace EMS\ClientHelperBundle\Entity;

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
        $this->synonyms[] = sprintf('%s:%s', $document['_type'], $document['_id']);
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
    public function makeShould($searchFields, $synonymsSearchField, $analyzerField, $boost=1.0)
    {

        $should = [];
        $should[] = $this->getQuery($searchFields, $analyzerField, $boost);
        
        foreach ($this->synonyms as $emsLink) {
            if(!empty($emsLink)){
                $should[] = $this->makeEmsLinkQuery($synonymsSearchField, $emsLink);                
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
    private function makeEmsLinkQuery($field, $query)
    {
        $searchField = ($field?$field:'_all');
        return [
            'match' => [
                $searchField => [
                    'query' => $query,
                    'operator' => 'AND',
                ]
            ]
        ];
    }
    
    public function getQuery($field, $analyzer, $boost=1.0) {
        
        $matches = [];
        preg_match_all('/^\"(.*)\"$/', $this->term, $matches);
        
        if(isset($matches[1][0])) {
            return [
                'match_phrase' => [
                    ($field?$field:'_all') => [
                        'analyzer' => $analyzer,
                        'query' => $this->getTerm(),  
                        'boost' => $boost
                    ]
                    
                ]
            ];
        }
        return [
            'match' => [
                ($field?$field:'_all') => [
                    'query' => $this->getTerm(),
                    'boost' => $boost,
                 ],
            ]
        ];
    }
}
