<?php

namespace EMS\ClientHelperBundle;

use EMS\ClientHelperBundle\DependencyInjection\Compiler\InjectClientRequestPass;
use Symfony\Cmf\Component\Routing\DependencyInjection\Compiler\RegisterRoutersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EMSClientHelperBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InjectClientRequestPass());
        $container->addCompilerPass(new RegisterRoutersPass('emch.routing.chain_router', 'emsch.router'));
    }
}
