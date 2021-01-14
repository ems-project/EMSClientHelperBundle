<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ClientHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        //override the default symfony router, with the chain router
        $container->setAlias('router', 'emsch.routing.chain_router');
        $container->getAlias('router')->setPublic(true);

        $this->processRouting($container);
    }

    public function processRouting(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('emsch.routing.client_request')) {
            return;
        }

        $clientRequest = $container->findDefinition($container->getParameter('emsch.routing.client_request'));

        if ($container->hasDefinition('emsch.routing.url.transformer')) {
            $container->getDefinition('emsch.routing.url.transformer')->setArgument(0, $clientRequest);
        }
    }
}
