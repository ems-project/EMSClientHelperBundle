<?php

namespace EMS\ClientHelperBundle\Elasticsearch;

use Elasticsearch\Client;
use Symfony\Component\HttpFoundation\RequestStack;

class ClientRequestFactory
{
    /**
     * @var RequestStack 
     */
    private $requestStack;
    
    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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
            $this->requestStack, 
            $indexPrefix
        );
    }
}
