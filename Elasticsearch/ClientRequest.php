<?php

namespace EMS\ClientHelperBundle\Elasticsearch;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\Service\RequestService;

class ClientRequest
{
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var RequestService
     */
    private $requestService;
    
    /**
     * @var string
     */
    private $indexPrefix;
    
    /**
     * @param Client         $client
     * @param RequestService $requestService
     * @param string         $indexPrefix
     */
    public function __construct(
        Client $client, 
        RequestService $requestService,
        $indexPrefix
    ) {
        $this->client = $client;
        $this->requestService = $requestService;
        $this->indexPrefix = $indexPrefix;
    }
        
    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->requestService->getLocale();
    }
    
    /**
     * @param string $type
     * @param string $id
     * 
     * @return array
     */
    public function get($type, $id)
    {
        return $this->client->get([
            'index' => $this->getIndex(),
            'type' => $type,
            'id' => $id,
        ]);
    }
    
    /**
     * @param string $type
     * @param array  $body
     * 
     * @return array
     */
    public function search($type, array $body)
    {
        return $this->client->search([
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
        ]);
    }
    
    /**
     * @param string $type
     * @param array  $body
     * 
     * @return array
     *
     * @throws Exception
     */
    public function searchOne($type, array $body)
    {
        $search = $this->search($type, $body);
        
        $hits = $search['hits'];
        
        if (1 != $hits['total']) {
            throw new \Exception(sprintf('expected 1 result, got %d', $hits['total']));
        }
        
        return $hits['hits'][0];
    }
    
    /**
     * @return string
     */
    private function getIndex()
    {
        return $this->indexPrefix . $this->requestService->getEnvironment();
    }
}
