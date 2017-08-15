<?php

namespace EMS\ClientHelperBundle\Frontend\Elasticsearch;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\Frontend\Service\RequestService;

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
