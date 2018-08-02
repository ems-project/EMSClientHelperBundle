<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection\Compiler;

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
    }
}
