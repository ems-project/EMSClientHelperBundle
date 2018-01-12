<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle;

use EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EMSRoutingBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        
        $container->addCompilerPass(new Compiler\ConfigPass());
        $container->addCompilerPass(new Compiler\ClientRequestPass());
    }
}
