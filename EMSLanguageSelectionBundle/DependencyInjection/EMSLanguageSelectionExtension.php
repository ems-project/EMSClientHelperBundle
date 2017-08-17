<?php

namespace EMS\ClientHelperBundle\EMSLanguageSelectionBundle\DependencyInjection;

use EMS\ClientHelperBundle\EMSLanguageSelectionBundle\Service\LanguageService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class EMSLanguageSelectionExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('backendBridgeServices.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadBackendBridgeServices($config, $container);

        $languageDefinition = new Definition(LanguageService::class);
        $emsClient = $config['ems_client'];
        $languageDefinition->setArguments([
            new ChildDefinition(sprintf('emsch.client_request.%s', $emsClient))
        ]);
        $languageDefinition->addMethodCall('setConfig', array($config));

        $container->setDefinition(
            sprintf('emsch.languageselection.language.service'),
            $languageDefinition
        );

        $loader->load('services.xml');
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function loadBackendBridgeServices(array $config, ContainerBuilder $container)
    {
        $emsClient = $config['ems_client'];
        $container
            ->setDefinition(
                sprintf('elasticsearch.client.%s', $emsClient),
                new ChildDefinition('elasticsearch.client')
            )
            ->setArguments([
                ['hosts' => $config['ems_hosts']]
            ]);
        $container
            ->setDefinition(
                sprintf('emsch.client_request.%s', $emsClient),
                new ChildDefinition('emsch.client_request')
            )
            ->setArguments([
                new Reference(sprintf('elasticsearch.client.%s', $emsClient)),
                $config['ems_index_prefix']
            ]);
    }
}
