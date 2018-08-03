<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection;

use Composer\CaBundle\CaBundle;
use Elasticsearch\Client;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Helper\Api\Client as ApiClient;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Translation\TranslationLoader;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig\TemplateLoader;
use EMS\CommonBundle\Elasticsearch\Factory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
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
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->processRequestEnvironments($container, $config['request_environments']);
        $this->processElasticms($container, $loader, $config['elasticms']);
        $this->processApi($container, $config['api']);
        if (isset($config['clear_cache'])) {
            $this->processClearCache($container, $config['clear_cache'], $config['elasticms']);
        }

        $this->processRequestEnvironments($container, $config['request_environments']);

        if (isset($config['twig_list'])) {
            $definition = $container->getDefinition('emsch.controller.twig_list');
            $definition->replaceArgument(1, $config['twig_list']['templates']);
        }

        $this->processLanguageSelection($container, $loader, $config['language_selection']);

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
     * @param XmlFileLoader    $loader
     * @param array            $config
     */
    private function processElasticms(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        foreach ($config as $name => $options) {
            $this->defineElasticsearchClient($container, $name, $options);
            $this->defineClientRequest($container, $loader, $name, $options);

            if (null !== $options['translation_type']) {
                $this->defineTranslationLoader($container, $name, $options);
            }

            if ($options['templates']) {
                $this->defineTemplateLoader($container, $name, $options['templates']);
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

            $container->setDefinition(sprintf('emsch.api_client.%s', $name), $definition);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $domain
     * @param array            $config
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
        $config = [
            'hosts' => $options['hosts'],
            'sSLVerification' => CaBundle::getBundledCaBundlePath(),
        ];

        $definition
            ->setFactory([new Reference('ems_common.elasticsearch.factory'), 'fromConfig'])
            ->setArgument(0, $config)
            ->setPublic(true);
        $definition->addTag('emsch.elasticsearch.client');
        
        $container->setDefinition(sprintf('elasticsearch.client.%s', $name), $definition);
    }

    /**
     * @param ContainerBuilder $container
     * @param XmlFileLoader    $loader
     * @param string           $name
     * @param array            $options
     */
    private function defineClientRequest(ContainerBuilder $container, XmlFileLoader $loader, $name, array $options)
    {
        $definition = new Definition(ClientRequest::class);
        $definition->setArguments([
            new Reference(sprintf('elasticsearch.client.%s', $name)),
            new Reference('emsch.request.service'),
            new Reference('logger'),
            $options,
            $name
        ]);
        $definition->addTag('emsch.client_request');
        $container->setDefinition(sprintf('emsch.client_request.%s', $name), $definition);

        if ($options['api']['enabled']) {
            $this->loadClientRequestApi($loader);
        }
    }

    /**
     * @param XmlFileLoader $loader
     */
    private function loadClientRequestApi(XmlFileLoader $loader)
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $loader->load('api.xml');
        $loaded = true;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param array $options
     */
    private function defineTranslationLoader(ContainerBuilder $container, $name, array $options)
    {
        $loader = new Definition(TranslationLoader::class);
        $loader->setArguments([
            new Reference(sprintf('elasticsearch.client.%s', $name)),
            $name,
            $options['index_prefix'],
            $options['translation_type']
        ]);
        $loader->addTag('translation.loader', ['alias' => $name]);

        $container->setDefinition(sprintf('translation.loader.%s', $name), $loader);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $name
     * @param array            $options
     */
    private function defineTemplateLoader(ContainerBuilder $container, $name, array $options)
    {
        $loader = new Definition(TemplateLoader::class);
        $loader->setArguments([
            new Reference(sprintf('emsch.client_request.%s', $name)),
            $options
        ]);
        $loader->addTag('twig.loader', ['alias' => $name, 'priority' => 1]);

        $container->setDefinition(sprintf('twig.loader.%s', $name), $loader);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $domain
     * @param string           $translationType
     */
    private function defineClearCacheListener(ContainerBuilder $container, $domain, $translationType)
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

    /**
     * @param ContainerBuilder $container
     * @param XmlFileLoader    $loader
     * @param array            $config
     */
    private function processLanguageSelection(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        if (!$config['enabled']) {
            return;
        }

        $container->setParameter('emsch.language_selection.client_request', $config['client_request']);
        $container->setParameter('emsch.language_selection.option_type', $config['option_type']);
        $container->setParameter('emsch.language_selection.supported_locale', $config['supported_locale']);

        $loader->load('language_selection.xml');
    }
}
