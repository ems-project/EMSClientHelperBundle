<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use Twig_Source;

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
    public function getSourceContext($name)
    {
        return new Twig_Source($this->getTemplate($name), $name);
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated used for php < 7
     */
    public function getSource($name)
    {
        return $this->getTemplate($name);
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
    private function getTemplate($name)
    {
        preg_match(self::REGEX, $name, $match);

        $search = \preg_replace('/\$template_name\$/', $match['template'], $this->config['search']);
        
        $document = $this->clientRequest->searchOneBy(
            $this->config['content_type'], 
            json_decode($search, true)
        );
        
        if (!$document) {
            throw new \Twig_Error_Loader('routing not found for template');
        }
        
        $source = $document['_source'];

        return $source[$this->config['field']];
    }
}
