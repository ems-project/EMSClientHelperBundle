<?php

namespace EMS\ClientHelperBundle\Twig;

use Symfony\Bridge\Twig\Extension\RoutingExtension as BaseExtension;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;

class RoutingExtension extends BaseExtension
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    
    /**
     * @param UrlGeneratorInterface $generator
     * @param RequestStack          $requestStack
     */
    public function __construct(
        UrlGeneratorInterface $generator, 
        RequestStack $requestStack
    ) {
        parent::__construct($generator);
        
        $this->requestStack = $requestStack;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction('ems_path', array($this, 'getPath'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPath($name, $parameters = array(), $relative = false)
    {
        $env = $this->requestStack->getCurrentRequest()->get('_environment');
        
        return parent::getPath(
            sprintf('%s_%s', $env, $name), 
            $parameters, 
            $relative
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emsch_routing';
    }
}
