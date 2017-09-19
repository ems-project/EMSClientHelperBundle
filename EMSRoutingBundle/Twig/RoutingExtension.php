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
     */
    const EMS_LINK = '/ems:\/\/(<em>)?object:(?P<type>.*?):(?P<ouuid>[[:alnum:]|-]*)/i';
    
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
            new \Twig_SimpleFilter('ems_routes', array($this, 'transform')),
        ];
    }
    
    /**
     * @param string $content
     *
     * @return string
     */
    public function transform($content)
    {
        return preg_replace_callback(self::EMS_LINK, function ($m) {
            $route = $this->routingService->generate($m['type'], $m['ouuid']);
            
            return $route ? $route : $m[0];
        }, $content);
    }
}
