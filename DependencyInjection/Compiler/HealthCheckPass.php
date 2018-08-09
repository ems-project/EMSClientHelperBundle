<?php

namespace EMS\ClientHelperBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class HealthCheckPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('emsch.command.health_check')) {
            return;
        }
        $command = $container->getDefinition('emsch.command.health_check');

        $this->addClients($container, $command);
        $this->addClientRequests($container, $command);
        $this->addStorageService($container, $command);
    }
    
    /**
     * @param ContainerBuilder $container
     * @param Definition       $command
     */
    private function addClients(ContainerBuilder $container, Definition $command)
    {
        $taggedServices = $container->findTaggedServiceIds('emsch.elasticsearch.client');
        $clients = [];
        foreach ($taggedServices as $id => $tags) {
            $clients[] = new Reference($id);
        }
        $command->addMethodCall('setClients', [$clients]);
    }
    
    /**
     * @param ContainerBuilder $container
     * @param Definition       $command
     */
    private function addClientRequests(ContainerBuilder $container, Definition $command)
    {
        $taggedServices = $container->findTaggedServiceIds('emsch.client_request');
        $clients = [];
        foreach ($taggedServices as $id => $tags) {
            $clients[] = new Reference($id);
        }
        $command->addMethodCall('setClientRequests', [$clients]);
    }
    
    /**
     * @param ContainerBuilder $container
     * @param Definition       $command
     */
    private function addStorageService(ContainerBuilder $container, Definition $command)
    {
        $taggedServices = $container->findTaggedServiceIds('emsch.storage_service');
        foreach ($taggedServices as $id => $tags) {
            $storageService = new Reference($id);
            $command->addMethodCall('setStorageService', [$storageService]);
            return;
        }
    }
}
