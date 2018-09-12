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
     * @var int
     */
    private $itemPerPage;

    /**
     * @param ClientRequestManager $clientRequestManager
     */
    public function __construct(ClientRequestManager $clientRequestManager)
    {
        $this->clientRequestManager = $clientRequestManager;
        $this->synonyms = ['typology', 'organization'];
        $this->facets = ['typology' => 15, 'ownership' => 15];
        $this->itemPerPage = 1000;
    }

    /**
     * @return ClientRequest
     */
    public function getClientRequest()
    {
        return $this->clientRequestManager->getDefault();
    }

    public function search($queryString, array $facets, $locale, $sortBy, $page, $sortOrder='asc')
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

        $body = [
            'query' => $query,
            'aggs' => $aggs,
            'suggest' => [
                'suggestion' => [
                    'text' => $queryString,
                    'term' => [
                        'field' => 'all_'.$locale,
                    ]
                ]
            ]
        ];

        if($sortBy) {
            $body['sort'] = [
                $sortBy => [
                    'order' => $sortOrder,
                    'missing' => '_last',
                    'unmapped_type' => 'long',
                ]
            ];
        }

        return $this->getClientRequest()->search('service', $body, $page*$this->itemPerPage, $this->itemPerPage);
    }
}