<?php

namespace EMS\ClientHelperBundle\EMSRedirectBundle;

use EMS\ClientHelperBundle\EMSRedirectBundle\DependencyInjection\Compiler\LoadKernelPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EMSRedirectBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new LoadKernelPass());
    }
}
