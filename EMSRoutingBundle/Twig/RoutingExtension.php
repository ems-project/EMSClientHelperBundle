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
     * Regex for searching ems links in content
     * content_type and query can be empty/optional
     * 
     * Regex101.com: 
     * ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\'|\s]*)))?
     * 
     * Example: <a href="ems://object:page:AV44kX4b1tfmVMOaE61u">example</a>
     * link_type => object, content_type => page, ouuid => AV44kX4b1tfmVMOaE61u
     */
    const EMS_LINK = '/ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\'|\s]*)))?/';
    
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
     *
     * @return string
     */
    public function transform($content, $locale=false)
    {
        return preg_replace_callback(self::EMS_LINK, function ($match) use (&$locale) {
            //array filter to remove empty capture groups
            $route = $this->routingService->generate(array_filter($match), $locale);

            return $route ? $route : $match[0];
        }, $content);
    }
}
