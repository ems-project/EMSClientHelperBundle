<?php

namespace EMS\ClientHelperBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InjectClientRequestPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('emsch.language_selection')) {
            $languageSelection = $container->findDefinition($container->getParameter('emsch.language_selection.client_request'));
            $container->getDefinition('emsch.language_selection')->setArgument(0, $languageSelection);
        }

        $this->processRouting($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function processRouting(ContainerBuilder $container)
    {
        if (!$container->hasParameter('emsch.routing.client_request')) {
            return;
        }

        $clientRequest = $container->findDefinition($container->getParameter('emsch.routing.client_request'));

        if ($container->hasDefinition('emsch.file_manager')) {
            $container->getDefinition('emsch.file_manager')->setArgument(0, $clientRequest);
        }

        if ($container->hasDefinition('emsch.routing.router')) {
            $container->getDefinition('emsch.routing.router')->setArgument(0, $clientRequest);
        }

        if ($container->hasDefinition('emsch.routing.link.transformer')) {
            $container->getDefinition('emsch.routing.link.transformer')->setArgument(0, $clientRequest);
        }

        if ($container->hasDefinition('emsch.routing.redirect_helper')) {
            $container->getDefinition('emsch.routing.redirect_helper')->setArgument(0, $clientRequest);
        }
    }
}
