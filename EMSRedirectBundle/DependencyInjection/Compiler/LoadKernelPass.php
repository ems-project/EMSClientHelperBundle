<?php

namespace EMS\ClientHelperBundle\EMSRedirectBundle\DependencyInjection\Compiler;

use EMS\ClientHelperBundle\EMSRedirectBundle\EventSubscriber\KernelSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class LoadKernelPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('EMS\ClientHelperBundle\EMSRedirectBundle\Service\RedirectService')) {
            return;
        }

        $redirectService = $container->findDefinition('EMS\ClientHelperBundle\EMSRedirectBundle\Service\RedirectService');
        $httpKernel = $container->getDefinition('http_kernel');

        $kernelDefinition = new Definition(KernelSubscriber::class);
        $kernelDefinition->setArguments([
            $redirectService,
            $httpKernel
        ]);
        $kernelDefinition->addTag('kernel.event_subscriber');
        $container->setDefinition(
            sprintf('emsch.redirect.kernel.subscriber'),
            $kernelDefinition
        );
    }
}
