<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\RequestService;
use Psr\Log\LoggerInterface;

class ClientRequestFactory
{
    /**
     * @var RequestService 
     */
    private $requestService;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param RequestService $requestService
     * @param LoggerInterface $logger
     */
    public function __construct(RequestService $requestService, LoggerInterface $logger)
    {
        $this->requestService = $requestService;
        $this->logger = $logger;
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
            $indexPrefix,
            $this->logger
        );
    }
}
