<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Twig;

use EMS\ClientHelperBundle\EMSRoutingBundle\Service\RoutingService;

/**
 * Routing Extension
 */
class RoutingExtension extends \Twig_Extension
{
    /**
     * @var RoutingService
     */
    private $routingService;
    
    /**
     * @param RoutingService $routingService
     */
    public function __construct(RoutingService $routingService)
    {
        $this->routingService = $routingService;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('emsch_routing', [$this, 'transform'], ['is_safe' => ['html']]),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('emsch_route', [$this, 'createUrl']),
        ];
    }
    
    /**
     * @param string $relativePath
     * @param string $path
     * @param array  $parameters
     *
     * @return string
     */
    public function createUrl($relativePath, $path, array $parameters = [])
    {
        $url = $this->routingService
            ->getUrlHelperService()
            ->createUrl($relativePath, $path);
        
        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }
        
        return $url;
        
    }
    
    /**
     * @param string $content
     * @param string $locale
     *
     * @return string
     */
    public function transform($content, $locale=null)
    {
        return $this->routingService->transform($content, $locale);
    }
}
