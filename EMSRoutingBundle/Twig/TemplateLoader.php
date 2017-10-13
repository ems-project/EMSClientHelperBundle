<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;

/**
 * Template Loader
 */
class TemplateLoader implements \Twig_LoaderInterface, \Twig_ExistsLoaderInterface
{
     /**
     * @var ClientRequest
     */
    private $clientRequest;
    
    /**
     * Injected by the compiler pass
     * 
     * @var array
     */
    private $config;
    
    const REGEX = '/(?P<template>.*?).ems.twig$/i';
    
    /**
     * @param ClientRequest $clientRequest injected by compiler pass
     */
    public function __construct(ClientRequest $clientRequest) 
    {
        $this->clientRequest = $clientRequest;
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
    public function exists($name)
    {
        return preg_match(self::REGEX, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        preg_match(self::REGEX, $name, $match);
        $document = $this->getDocument($match['template']);
        
        return $document[$this->config['field']];
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        return $this->config['cache'];
    }
    
    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
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
        $search = \preg_replace('/\$template_name\$/', $name, $this->config['search']);
        
        $document = $this->clientRequest->searchOneBy(
            $this->config['content_type'], 
            json_decode($search, true)
        );
        
        if (!$document) {
            throw new \Twig_Error_Loader('routing not found for template');
        }
        
        return $document['_source'];
    }
}
