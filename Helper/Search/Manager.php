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
     * @param ClientRequestManager $clientRequestManager
     */
    public function __construct(ClientRequestManager $clientRequestManager)
    {
        $this->clientRequestManager = $clientRequestManager;
    }

    /**
     * @return ClientRequest
     */
    public function getClientRequest(): ClientRequest
    {
        return $this->clientRequestManager->getDefault();
    }

    public function search($queryString, $locale)
    {
        $clientRequest = $this->getClientRequest();

        $analyzers = [new AnalyserSet($clientRequest, 'all_'.$locale)];

        $qbService = new QueryBuilderService();
        $query = $qbService->getQuery($queryString, $analyzers);

        return $this->getClientRequest()->search('service', [
            'query' => $query
        ]);
    }
}