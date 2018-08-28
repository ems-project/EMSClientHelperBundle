<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Entity\AnalyserSet;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Service\QueryBuilderService;

class Manager
{
    /**
     * @var ClientRequestManager
     */
    private $clientRequestManager;

    /**
     * @var array
     */
    private $synonyms;

    /**
     * @var array
     */
    private $facets;

    /**
     * @param ClientRequestManager $clientRequestManager
     */
    public function __construct(ClientRequestManager $clientRequestManager)
    {
        $this->clientRequestManager = $clientRequestManager;
        $this->synonyms = ['typology', 'organization'];
        $this->facets = ['typology' => 15, 'ownership' => 15];
    }

    /**
     * @return ClientRequest
     */
    public function getClientRequest(): ClientRequest
    {
        return $this->clientRequestManager->getDefault();
    }

    public function search($queryString, array $facets, $locale)
    {
        $clientRequest = $this->getClientRequest();

        $filter = '';
        if(!empty($facets))
        {
            $filter = [];
            foreach($facets as $field => $terms) {
                if(!empty($terms)) {
                    $filter['bool']['must'][] = [
                        'terms' => [
                            $field => $terms,
                        ]
                    ];
                }
            }
        }


        $analyzers = [new AnalyserSet($clientRequest, 'all_'.$locale, $filter, $this->synonyms, empty($this->synonyms)?false:('all_'.$locale), '_all')];

        $qbService = new QueryBuilderService();
        $query = $qbService->getQuery($queryString, $analyzers);

        $aggs = [];
        if(!empty($this->facets)) {
            foreach ($this->facets as $facet => $size) {
                $aggs[$facet] = [
                    'terms' => [
                        'field' => $facet,
                        'size' => $size,
                ]];
            }
        }

        return $this->getClientRequest()->search('service', [
            'query' => $query,
            'aggs' => $aggs,
        ]);
    }
}