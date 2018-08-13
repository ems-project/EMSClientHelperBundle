<?php

namespace EMS\ClientHelperBundle;

use EMS\ClientHelperBundle\DependencyInjection\Compiler\ApiClientPass;
use EMS\ClientHelperBundle\DependencyInjection\Compiler\HealthCheckPass;
use EMS\ClientHelperBundle\DependencyInjection\Compiler\InjectClientRequestPass;
use EMS\ClientHelperBundle\DependencyInjection\Compiler\LoadDebugBarPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EMSClientHelperBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InjectClientRequestPass());
    }
}
