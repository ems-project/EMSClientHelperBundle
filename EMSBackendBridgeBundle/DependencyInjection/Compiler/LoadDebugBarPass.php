<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoadDebugBarPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('emsch.logger')) {
            return;
        }

        $services = $container->getServiceIds();

        foreach ($services as $id) {
            if (strpos($id, 'emsch.client_request.') !== false) {
                $definition = $container->getDefinition($id);
                $definition->addMethodCall('setClientHelperLogger', [new Reference('emsch.logger')]);                
            }
        }
    }
}
