<?php

namespace EMS\ClientHelperBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        $elasticms = $config['elasticms'];
        
        foreach ($elasticms as $project => $projectConfig) {
            $this->loadProject($project, $projectConfig, $container);
        }

        $container->setParameter('ems_client_helper.twig_list.templates', $config['twig_list']['templates']);
        $container->setParameter('ems_client_helper.twig_list.app_enabled', $config['twig_list']['app_enabled']);
        $container->setParameter('ems_client_helper.twig_list.app_base_path', $config['twig_list']['app_base_path']);
    }
    
    /**
     * @param string           $project
     * @param array            $config
     * @param ContainerBuilder $container
     */
    protected function loadProject($project, array $config, ContainerBuilder $container)
    {
        $container
            ->setDefinition(
                sprintf('emsch.client.%s', $project), 
                new ChildDefinition('emsch.client')
            )
            ->setArguments([
                ['hosts' => $config['clusters']]
            ]);
        
        $container
            ->setDefinition(
                sprintf('emsch.client_request.%s', $project), 
                new ChildDefinition('emsch.client_request')
            )
            ->setArguments([
                new Reference(sprintf('emsch.client.%s', $project)),
                $config['index_prefix']
            ]);
    }
}
