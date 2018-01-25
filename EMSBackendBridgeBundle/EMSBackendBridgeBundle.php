<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection\Compiler\LoadDebugBarPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EMSBackendBridgeBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new LoadDebugBarPass());
    }
}
