<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection\Compiler;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSRoutingBundle\Controller\DynamicController;
use EMS\ClientHelperBundle\EMSRoutingBundle\Routing\EMSLoader;
use EMS\ClientHelperBundle\EMSRoutingBundle\Routing\Router;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\FileManager;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\RoutingService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Twig\TemplateLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class ClientRequestPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $id = $container->getParameter('ems_routing.client_request');
        
        if (!$container->has($id)) {
            throw new InvalidArgumentException(
                sprintf("ems_routing.client_request service '%s' not found!", $id)
            );
        }
        
        $routingService = $container->getDefinition(RoutingService::class);
        $routingService->setArgument(0, $container->findDefinition($id));
        
        $twigLoader = $container->getDefinition(Router::class);
        $twigLoader->setArgument(0, $container->findDefinition($id));
    }
}
