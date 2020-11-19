<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\DependencyInjection;

use Composer\CaBundle\CaBundle;
use Elasticsearch\Client;
use EMS\ClientHelperBundle\Helper\Api\Client as ApiClient;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Twig\TwigLoader;
use EMS\CommonBundle\Logger\ElasticsearchLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EMSClientHelperExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('routing.xml');
        $loader->load('search.xml');
        $loader->load('user_api.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('emsch.locales', $config['locales']);
        $container->setParameter('emsch.bind_locale', $config['bind_locale'] ?? true);
        $container->setParameter('emsch.etag_hash_algo', $config['etag_hash_algo'] ?? 'sha1');
        $container->setParameter('emsch.assets.enabled', $config['dump_assets']);
        $container->setParameter('emsch.request_environments', $config['request_environments']);

        $templates = $config['templates'];
        $container->setParameter('emsch.templates', $config['templates']);
        $container->getDefinition('emsch.helper_exception')->replaceArgument(3, $templates['error']);
        $container->getDefinition('emsch.routing.url.transformer')->replaceArgument(4, $templates['ems_link']);

        $this->defineElasticsearchLogger($container, $config['log']);

        $this->processElasticms($container, $loader, $config['elasticms']);
        $this->processApi($container, $config['api']);
        $this->processRoutingSelection($container, $config['routing']);
        $this->processUserApi($container, $config['user_api']);

        if (isset($config['twig_list'])) {
            $definition = $container->getDefinition('emsch.controller.twig_list');
            $definition->replaceArgument(1, $config['twig_list']['templates']);
        }
    }

    private function processElasticms(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        foreach ($config as $name => $options) {
            $this->defineElasticsearchClient($container, $name, $options);
            $this->defineClientRequest($container, $loader, $name, $options);

            if (isset($options['templates'])) {
                $this->defineTwigLoader($container, $name, $options['templates']);
            }
        }
    }

    private function processApi(ContainerBuilder $container, array $config)
    {
        foreach ($config as $name => $options) {
            $definition = new Definition(ApiClient::class);
            $definition->setArgument(0, $name);
            $definition->setArgument(1, $options['url']);
            $definition->setArgument(2, $options['key']);
            $definition->setArgument(3, new Reference('logger'));
            $definition->addTag('emsch.api_client');

            $container->setDefinition(\sprintf('emsch.api_client.%s', $name), $definition);
        }
    }

    /**
     * @param string $name
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

        $container->setDefinition(\sprintf('ems_common.elasticsearch.%s', $name), $definition);
    }

    private function defineElasticsearchLogger(ContainerBuilder $container, array $options)
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

        $container->setDefinition('ems_common.elasticsearch.log.client', $definition);

        $definition = new Definition(ElasticsearchLogger::class);
        $definition->setArguments([
            'info',
            $options['instance_id'],
            '',
            'ems_client',
            new Reference('ems_common.elasticsearch.log.client'),
            new Reference('security.helper'),
            $options['by_pass'],
        ]);
        $definition->addTag('kernel.cache_warmer');
        $definition->addTag('kernel.event_listener', [
            'event' => 'kernel.terminate',
        ]);

        $container->setDefinition('ems_common.elasticsearch.logger', $definition);
    }

    /**
     * @param string $name
     */
    private function defineClientRequest(ContainerBuilder $container, XmlFileLoader $loader, $name, array $options)
    {
        $definition = new Definition(ClientRequest::class);
        $definition->setArguments([
            new Reference(\sprintf('ems_common.elasticsearch.%s', $name)),
            new Reference('emsch.helper_environment'),
            new Reference('logger'),
            new Reference('cache.app'),
            $name,
            $options,
        ]);
        $definition->addTag('emsch.client_request');

        if (isset($options['api'])) {
            $this->loadClientRequestApi($loader);
            $definition->addTag('emsch.client_request.api');
        }

        $container->setDefinition(\sprintf('emsch.client_request.%s', $name), $definition);
    }

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
     * @param string $name
     * @param array  $options
     */
    private function defineTwigLoader(ContainerBuilder $container, $name, $options)
    {
        $loader = new Definition(TwigLoader::class);
        $loader->setArguments([
            new Reference(\sprintf('emsch.client_request.%s', $name)),
            $options,
        ]);
        $loader->addTag('twig.loader', ['alias' => $name, 'priority' => 1]);

        $container->setDefinition(\sprintf('emsch.twig.loader.%s', $name), $loader);
    }

    private function processRoutingSelection(ContainerBuilder $container, array $config)
    {
        if (!$config['enabled']) {
            return;
        }

        $container->setParameter('emsch.routing.client_request', $config['client_request']);
        $container->setParameter('emsch.routing.routes', $config['routes']);
        $container->setParameter('emsch.routing.redirect_type', $config['redirect_type']);
        $container->setParameter('emsch.routing.relative_paths', $config['relative_paths']);
    }

    /**
     * @param array<string> $config
     */
    private function processUserApi(ContainerBuilder $container, array $config): void
    {
        if (!$config['enabled']) {
            return;
        }

        $container->setParameter('emsch.user_api.url', $config['url']);
    }
}
