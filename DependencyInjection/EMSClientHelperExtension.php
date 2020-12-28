<?php

namespace EMS\ClientHelperBundle\DependencyInjection;

use EMS\ClientHelperBundle\EventListener\RedirectListener;
use EMS\ClientHelperBundle\Helper\Api\Client as ApiClient;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Routing\RedirectHelper;
use EMS\ClientHelperBundle\Helper\Routing\Router;
use EMS\ClientHelperBundle\Helper\Routing\Url\Generator;
use EMS\ClientHelperBundle\Helper\Routing\Url\Transformer;
use EMS\ClientHelperBundle\Helper\Twig\TwigLoader;
use EMS\ClientHelperBundle\Twig\RoutingRuntime;
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

        $this->defineElasticsearchLogger($container, $config['log']);

        $this->processElasticms($container, $loader, $config['elasticms']);
        $this->processApi($container, $config['api']);
        if (isset($config['routing'])) {
            $this->processRoutingSelection($container, $config['routing'], $config['locales'], $config['templates']);
            $container->getDefinition('emsch.routing.url.transformer')->replaceArgument(4, $templates['ems_link']);
        }
        $this->processUserApi($container, $config['user_api']);

        if (isset($config['twig_list'])) {
            $definition = $container->getDefinition('emsch.controller.twig_list');
            $definition->replaceArgument(1, $config['twig_list']['templates']);
        }
    }

    private function processElasticms(ContainerBuilder $container, XmlFileLoader $loader, array $config)
    {
        foreach ($config as $name => $options) {
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

    private function defineElasticsearchLogger(ContainerBuilder $container, array $options)
    {
        $definition = new Definition(ElasticsearchLogger::class);
        $definition->setArguments([
            'info',
            $options['instance_id'],
            '',
            'ems_client',
            new Reference('ems_common.elastica.client'),
            new Reference('security.helper'),
            new Reference('ems_common.service.mapping'),
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
            new Reference('ems_common.service.elastica'),
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

    private function processRoutingSelection(ContainerBuilder $container, array $config, $locales, $templates)
    {
        $container->setParameter('emsch.routing.client_request', $config['client_request']);
        $container->setParameter('emsch.routing.routes', $config['routes']);
        $container->setParameter('emsch.routing.redirect_type', $config['redirect_type']);
        $container->setParameter('emsch.routing.relative_paths', $config['relative_paths']);

        echo $config['client_request'];

        $router = new Definition(Router::class);
        $router->setArguments([
            new Reference('emsch.manager.client_request'),
            new Reference('emsch.helper_cache'),
            $locales,
            $templates,
            $config['routes'],
        ]);
        $router->addTag('emsch.router', ['priority' => 100]);
        $container->setDefinition('emsch.router', $router);


        $generator = new Definition(Generator::class);
        $generator->setArguments([
            new Reference('Symfony\\Component\\Routing\\RouterInterface'),
            $config['relative_paths'],
        ]);
        $container->setDefinition('emsch.routing.url.generator', $generator);

        $transformer = new Definition(Transformer::class);
        $transformer->setArguments([
            new Reference(\sprintf('emsch.client_request.%s', $config['client_request'])),
            new Reference('emsch.routing.url.generator'),
            new Reference('twig'),
            new Reference('logger'),
            null,
        ]);
        $transformer->addTag('monolog.logger', ['channel' => 'emsch_routing']);
        $container->setDefinition('emsch.routing.url.transformer', $transformer);

        $redirectHelper = new Definition(RedirectHelper::class);
        $redirectHelper->setArguments([
            new Reference(\sprintf('emsch.client_request.%s', $config['client_request'])),
            new Reference('emsch.routing.url.transformer'),
            $config['redirect_type'],
        ]);
        $container->setDefinition('emsch.routing.redirect_helper', $redirectHelper);

        $redirectListener = new Definition(RedirectListener::class);
        $redirectListener->setArguments([
            new Reference('emsch.routing.redirect_helper'),
            new Reference('http_kernel'),
            new Reference('Symfony\\Component\\Routing\\RouterInterface'),
        ]);
        $redirectListener->addTag('kernel.event_subscriber');
        $container->setDefinition('emsch.redirect_listener', $redirectListener);

        $routing = new Definition(RoutingRuntime::class);
        $routing->setArguments([
            new Reference('emsch.routing.url.transformer'),
        ]);
        $routing->addTag('twig.runtime');
        $container->setDefinition('emsch.twig.runtime.routing', $routing);


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
