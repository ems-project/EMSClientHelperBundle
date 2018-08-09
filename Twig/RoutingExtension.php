<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Routing\Link\Transformer;

class RoutingExtension extends \Twig_Extension
{
    /**
     * @var Transformer
     */
    private $transformer;
    
    /**
     * @param Transformer $transformer
     */
    public function __construct(Transformer $transformer)
    {
        $this->transformer = $transformer;
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
        $url = $this->transformer
            ->getGenerator()
            ->createUrl($relativePath, $path);
        
        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }
        
        return $url;
        
    }
    
    /**
     * @param string $content
     * @param string $locale
     * @param string $baseUrl
     *
     * @return string
     */
    public function transform($content, $locale = null, $baseUrl = null)
    {
        return $this->transformer->transform($content, $locale, $baseUrl);
    }
}
