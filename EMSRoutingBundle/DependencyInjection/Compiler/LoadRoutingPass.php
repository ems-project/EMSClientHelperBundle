<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection\Compiler;

use EMS\ClientHelperBundle\EMSRoutingBundle\Service\RoutingService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Twig\RoutingTemplateLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class LoadRoutingPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter('ems_routing.client_request')) {
            $this->injectClientRequest($container);
        }
    }
    
    /**
     * @param ContainerBuilder $container
     *
     * @throws InvalidArgumentException
     */
    private function injectClientRequest(ContainerBuilder $container)
    {
        $id = $container->getParameter('ems_routing.client_request');
        
        if (!$container->has($id)) {
            throw new InvalidArgumentException(
                sprintf("ems_routing.client_request service '%s' not found!", $id)
            );
        }
        
        $twigExtension = $container->getDefinition(RoutingService::class);
        $twigExtension->setArgument(0, $container->findDefinition($id));
        
        $twigLoader = $container->getDefinition(RoutingTemplateLoader::class);
        $twigLoader->setArgument(0, $container->findDefinition($id));
    }
}
