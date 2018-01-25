<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoadDebugBarPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('emsch.logger')) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('emsch.client_request');
        $logger = new Reference('emsch.logger');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->addMethodCall('setClientHelperLogger', [$logger]);
        }
    }
}
