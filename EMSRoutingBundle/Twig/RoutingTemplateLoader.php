<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;

/**
 * Routing Template Loader
 */
class RoutingTemplateLoader implements \Twig_LoaderInterface
{
     /**
     * @var ClientRequest
     */
    private $clientRequest;
    
    /**
     * @var bool
     */
    private $enableCache;
    
    /**
     * @param ClientRequest $clientRequest injected by compiler pass
     * @param bool          $enableCache
     */
    public function __construct(ClientRequest $clientRequest, $enableCache) 
    {
        $this->clientRequest = $clientRequest;
        $this->enableCache = $enableCache;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        $document = $this->getDocument($name);
        
        return $document['template'];
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        return $this->enableCache;
    }
    
    /**
     * @param string $name
     *
     * @return array
     *
     * @throws \Twig_Error_Loader
     */
    private function getDocument($name)
    {
        $document = $this->clientRequest->searchOneBy('routing', [
            'content_type' => $name
        ]);
        
        if (!$document) {
            throw new \Twig_Error_Loader('routing not found for template');
        }
        
        return $document['_source'];
    }
}
