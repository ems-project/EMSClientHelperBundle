<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\RequestService;

class ClientRequestFactory
{
    /**
     * @var RequestService 
     */
    private $requestService;
    
    /**
     * @param RequestService $requestService
     */
    public function __construct(RequestService $requestService)
    {
        $this->requestService = $requestService;
    }
    
    /**
     * @param Client $client
     * @param string $indexPrefix
     *
     * @return ClientRequest
     */
    public function create(Client $client, $indexPrefix)
    {
        return new ClientRequest(
            $client, 
            $this->requestService, 
            $indexPrefix
        );
    }
}
