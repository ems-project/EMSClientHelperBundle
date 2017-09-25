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
     * ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>[[:alnum:]|-]*)(?:\?(?P<query>.*?)(?:"|'|\s))?
     * 
     * Example: <a href="ems://object:page:AV44kX4b1tfmVMOaE61u">example</a>
     * link_type => object, content_type => page, ouuid => AV44kX4b1tfmVMOaE61u
     */
    const EMS_LINK = '/ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>[[:alnum:]|-]*)(?:\?(?P<query>.*?)(?:"|\'|\s))?/i';
    
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
     * @param string $content
     *
     * @return string
     */
    public function transform($content)
    {
        return preg_replace_callback(self::EMS_LINK, function ($match) {
            //array filter to remove empty capture groups
            $route = $this->routingService->generate(array_filter($match));
            
            return $route ? $route : $match[0];
        }, $content);
    }
}
