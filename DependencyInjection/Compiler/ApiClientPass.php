<?php

namespace EMS\ClientHelperBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiClientPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('emsch.api')) {
            return;
        }

        $apiService = $container->findDefinition('emsch.api');
        $clientRequests = $container->findTaggedServiceIds('emsch.client_request');

        foreach ($clientRequests as $id => $tags) {
            $apiService->addMethodCall('addClientRequest', [new Reference($id)]);
        }
    }
}
