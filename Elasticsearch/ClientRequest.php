<?php

namespace EMS\ClientHelperBundle\Elasticsearch;

use Elasticsearch\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ClientRequest
{
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var RequestStack
     */
    private $requestStack;
    
    /**
     * @var string
     */
    private $indexPrefix;
    
    /**
     * @param Client       $client
     * @param RequestStack $requestStack
     * @param string       $indexPrefix
     */
    public function __construct(
        Client $client, 
        RequestStack $requestStack,
        $indexPrefix
    ) {
        $this->client = $client;
        $this->requestStack = $requestStack;
        $this->indexPrefix = $indexPrefix;
    }
    
    /**
     * @param string $type
     * @param array  $body
     */
    public function search($type, array $body)
    {
        return $this->client->search([
            'index' => $this->indexPrefix . $this->getEnvironment(),
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
     * @throws \Exception
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
    public function getLocale()
    {
        return $this->getCurrentRequest()->get('_locale');
    }
    
    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->getCurrentRequest()->get('_environment');
    }
    
    /**
     * @return Request
     *
     * @throws \Exception
     */
    private function getCurrentRequest()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        
        if (null === $currentRequest) {
            throw new \Exception('no request!');
        }
        
        return $currentRequest;
    }
}
