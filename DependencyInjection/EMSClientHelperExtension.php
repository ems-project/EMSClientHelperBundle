<?php

namespace EMS\ClientHelperBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class EMSClientHelperExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
      
        $container->setParameter('ems_client_helper.twig_list.templates', $config['twig_list']['templates']);
        $container->setParameter('ems_client_helper.twig_list.app_enabled', $config['twig_list']['app_enabled']);
        $container->setParameter('ems_client_helper.twig_list.app_base_path', $config['twig_list']['app_base_path']);
   
    }
}
