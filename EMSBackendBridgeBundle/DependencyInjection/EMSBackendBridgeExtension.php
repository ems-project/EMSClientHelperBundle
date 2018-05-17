<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Api\ApiClient;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Translation\TranslationLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\ClearCacheService;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\EventListener\ClearCacheRequestListener;

/**
 * Load ems backend bridge services and process configuration
 */
class EMSBackendBridgeExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->processRequestEnvironments($container, $config['request_environments']);
        $this->processElasticms($container, $config['elasticms']);
        $this->processApi($container, $config['api']);
        if (isset($config['clear_cache'])) {
            $this->processClearCache($container, $config['clear_cache'], $config['elasticms']);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     *
     * @return void
     */
    private function processRequestEnvironments(ContainerBuilder $container, array $config)
    {
        $id = 'emsch.request_listener';

        if (!$container->hasDefinition($id)) {
            return;
        }

        $eventListener = $container->getDefinition('emsch.request_listener');

        foreach ($config as $environment => $options) {
            $eventListener->addMethodCall('addRequestEnvironment', [
                $environment, $options['regex'], $options['index'], $options['backend']
            ]);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function processElasticms(ContainerBuilder $container, array $config)
    {
        foreach ($config as $name => $options) {
            $this->defineElasticsearchClient($container, $name, $options);
            $this->defineClientRequest($container, $name, $options);

            if (null !== $options['translation_type']) {
                $this->defineTranslationLoader($container, $name, $options);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function processApi(ContainerBuilder $container, array $config)
    {
        foreach ($config as $name => $options) {
            $definition = new Definition(ApiClient::class);
            $definition->setArgument(0, $options['url']);
            $definition->setArgument(1, $options['key']);

            $container->setDefinition(sprintf('emsch.api.%s', $name), $definition);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     */
    private function processClearCache(ContainerBuilder $container, $domain, array $config)
    {
        if (!isset($config[$domain]['translation_type'])) {
            return;
        }
        $clientRequest = 'elasticsearch.client.' . $domain;

        if ($container->hasDefinition($clientRequest)) {
            $translationType = $config[$domain]['translation_type'];
            $this->defineClearCacheListener($container, $domain, $translationType);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param array $options
     */
    private function defineElasticsearchClient(ContainerBuilder $container, $name, array $options)
    {
        $definition = new Definition(Client::class);
        $config = ['hosts' => $options['hosts']];

        foreach ($options['hosts'] as $host){
            if((substr($host, 0, 8) === 'https://')){
                $caBundle = \Composer\CaBundle\CaBundle::getBundledCaBundlePath();
                $config['sSLVerification'] = $caBundle;
                break;
            }
        }

        $definition
            ->setFactory(['Elasticsearch\ClientBuilder', 'fromConfig'])
            ->setArgument(0, $config)
            ->setPublic(true);
        $definition->addTag('emsch.elasticsearch.client');
        
        $container->setDefinition(sprintf('elasticsearch.client.%s', $name), $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param array $options
     */
    private function defineClientRequest(ContainerBuilder $container, $name, array $options)
    {
        $definition = new Definition(ClientRequest::class);
        $definition->setArguments([
            new Reference(sprintf('elasticsearch.client.%s', $name)),
            new Reference('emsch.request.service'),
            new Reference('logger'),
            $options
        ]);
        $definition->addTag('emsch.client_request');

        $container->setDefinition(sprintf('emsch.client_request.%s', $name), $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param array $options
     */
    protected function defineTranslationLoader(ContainerBuilder $container, $name, array $options)
    {
        $loader = new Definition(TranslationLoader::class);
        $loader->setArguments([
            new Reference(sprintf('elasticsearch.client.%s', $name)),
            $name,
            $options['index_prefix'],
            $options['translation_type']
        ]);
        $loader->addTag('translation.loader', ['alias' => $name]);

        $container->setDefinition(
            sprintf('translation.loader.%s', $name),
            $loader
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param array $options
     */
    protected function defineClearCacheListener(ContainerBuilder $container, $domain, $translationType)
    {
        $cachePath = $container->getParameter('kernel.cache_dir');

        $clearCacheService = new Definition(ClearCacheService::class);
        $clearCacheService->setArguments([
            $cachePath,
            new Reference('translator'),
            new Reference('emsch.request.service'),
            new Reference('emsch.client_request.' . $domain),
            $translationType

        ]);
        $container->setDefinition('emsch.clear_cache.service', $clearCacheService);

        $clearCacheListener = new Definition(ClearCacheRequestListener::class);
        $clearCacheListener->setArguments([
            new Reference('emsch.clear_cache.service')
        ]);
        $clearCacheListener->addTag('kernel.event_listener', array(
            'event' => 'kernel.request',
            'priority' => 90

        ));
        $container->setDefinition(
            'emsch.clear_cache_listener',
            $clearCacheListener
        );
    }
}
