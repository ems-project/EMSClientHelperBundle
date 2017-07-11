<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Service\RequestService;
use Symfony\Bridge\Twig\Extension\RoutingExtension as BaseExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFunction;

class RoutingExtension extends BaseExtension
{
    /**
     * @var RequestService
     */
    private $requestService;
    
    /**
     * @param UrlGeneratorInterface $generator
     * @param RequestService        $requestService
     */
    public function __construct(
        UrlGeneratorInterface $generator,
        RequestService $requestService
    ) {
        parent::__construct($generator);
        
        $this->requestService = $requestService;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('emsch_path', array($this, 'getPath'), array('is_safe_callback' => array($this, 'isUrlGenerationSafe'))),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPath($name, $parameters = array(), $relative = false)
    {
        return parent::getPath(
            sprintf('%s_%s', $this->requestService->getEnvironment(), $name), 
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
