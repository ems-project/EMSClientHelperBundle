<?php

namespace EMS\ClientHelperBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        //override the default symfony router, with the chain router
        $container->setAlias('router', 'emsch.routing.chain_router');
        $container->getAlias('router')->setPublic(true);

        $this->processRouting($container);
    }

    public function processRouting(ContainerBuilder $container)
    {
        if (!$container->hasParameter('emsch.routing.client_request')) {
            return;
        }

        $definitionClientRequest = \strval($container->getParameter('emsch.routing.client_request'));
        $clientRequest = $container->findDefinition($definitionClientRequest);

        if ($container->hasDefinition('emsch.routing.url.transformer')) {
            $container->getDefinition('emsch.routing.url.transformer')->setArgument(0, $clientRequest);
        }

        if ($container->hasDefinition('emsch.routing.redirect_helper')) {
            $container->getDefinition('emsch.routing.redirect_helper')->setArgument(0, $clientRequest);
        }
    }
}
