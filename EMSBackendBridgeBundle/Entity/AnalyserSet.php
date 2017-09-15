<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;

class AnalyserSet {
    /**
     *
     * @var string
     */
    private $searchField;
    /**
     *
     * @var string
     */
    private $synonymsSearchField;
    /**
     *
     * @var string
     */
    private $filter;
    /**
     *
     * @var string|array
     */
    private $synonymsFilter;
    /**
     *
     * @var ClientRequest
     */
    private $clientRequest;
    /**
     *
     * @var string|array
     */
    private $synonymTypes;
    /**
     *
     * @var string
     */
    private $searchSynonymsInField;
    
    public function __construct(ClientRequest $clientRequest, $searchField, $filter='', $synonymTypes=[], $synonymsSearchField=false, $searchSynonymsInField=false, $synonymsFilter=''){
        $this->clientRequest = $clientRequest;
        $this->searchField = $searchField;
        $this->filter = $filter;
        $this->synonymTypes = $synonymTypes;
        $this->synonymsSearchField = $synonymsSearchField;
        $this->searchSynonymsInField = $searchSynonymsInField;
        $this->synonymsFilter = $synonymsFilter;
    }
    
    /**
     *
     * @return string
     */
    public function getSearchField(){
        return $this->searchField;
    }
    
    /**
     *
     * @return string
     */
    public function getFilter(){
        if(is_string($this->filter)) {
            return json_decode($this->filter, true);            
        }
        return $this->filter;
    }
    
    /**
     *
     * @return string
     */
    public function getSearchSynonymsInField(){
        return $this->searchSynonymsInField;
    }
    
    /**
     *
     * @return string
     */
    public function getSynonymsSearchField(){
        return $this->synonymsSearchField;
    }
    
    /**
     *
     * @return string|array
     */
    public function getSynonymTypes(){
        return $this->synonymTypes;
    }
    
    
    /**
     *
     * @return string|array
     */
    public function getSynonymsFilter(){
        if(is_string($this->synonymsFilter)){
            return json_decode($this->synonymsFilter, true);            
        }
        return $this->synonymsFilter;
    }
    
    /**
     * 
     * @return \EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest
     */
    public function getClientRequest(){
        return $this->clientRequest;
    }
}

