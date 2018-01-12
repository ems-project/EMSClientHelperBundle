<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Extension class
 */
class EMSRoutingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        $container->setParameter('ems_routing.client_request', $config['client_request']);
        $container->setParameter('ems_routing.template_loader', $config['template_loader']);
        $container->setParameter('ems_routing.file_manager', $config['file_manager']);
        $container->setParameter('ems_routing.url_helper', $config['url_helper']);
    }
}
