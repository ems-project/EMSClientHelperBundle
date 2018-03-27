<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection\Compiler;

use EMS\ClientHelperBundle\EMSRoutingBundle\EventSubscriber\KernelSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class LoadRedirectKernelPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('EMS\ClientHelperBundle\EMSRoutingBundle\Service\RedirectService')) {
            return;
        }

        $redirectService = $container->findDefinition('EMS\ClientHelperBundle\EMSRoutingBundle\Service\RedirectService');

        $httpKernel = $container->getDefinition('http_kernel');
        $router = $container->getDefinition('router.default');

        $kernelDefinition = new Definition(KernelSubscriber::class);
        $kernelDefinition->setArguments([
            $redirectService,
            $httpKernel,
            $router
        ]);
        $kernelDefinition->addTag('kernel.event_subscriber');
        $container->setDefinition(
            sprintf('emsch.redirect.kernel.subscriber'),
            $kernelDefinition
        );
    }
}
