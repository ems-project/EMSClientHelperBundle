<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\FileManager;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\RedirectService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\UrlHelperService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Twig\TemplateLoader;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        /* @var $rootNode ArrayNodeDefinition */
        $rootNode = $treeBuilder->root('ems_routing_bundle');
        $rootNode
            ->children()
                ->scalarNode('client_request')
                    ->info(ClientRequest::class)
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;
        
        $this->addTemplateLoaderSection($rootNode);
        $this->addFileManagerSection($rootNode);
        $this->addUrlHelperSection($rootNode);
        $this->addRedirectSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addTemplateLoaderSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('template_loader')
                    ->info(TemplateLoader::class)
                    ->children()
                        ->scalarNode('content_type')
                            ->cannotBeEmpty()
                            ->defaultValue('routing')
                        ->end()
                        ->scalarNode('search')
                            ->info('json body for searching the template, placeholder $template$')
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('field')
                            ->info('document template field')
                            ->cannotBeEmpty()
                            ->defaultValue('template')
                        ->end()
                        ->booleanNode('cache')
                            ->info('enable twig cache')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addFileManagerSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('file_manager')
                    ->info(FileManager::class)
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('content_type')->cannotBeEmpty()->end()
                        ->arrayNode('property_paths')
                            ->children()
                                ->scalarNode('filename')->cannotBeEmpty()->end()
                                ->scalarNode('mimetype')->cannotBeEmpty()->end()
                                ->scalarNode('sha1')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addUrlHelperSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('url_helper')
                    ->info(UrlHelperService::class)
                    ->canBeEnabled()
                    ->children()
                        ->arrayNode('relative_paths')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('regex')
                                    ->info('regex for matching the content_type')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('path')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addRedirectSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('redirection')
                    ->info(RedirectService::class)
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('redirect_type')
                            ->cannotBeEmpty()
                            ->info('content type used to define redirection')
                        ->end()
                        ->scalarNode('client_request')
                            ->cannotBeEmpty()
                            ->info('elasticms client defined in EMSBackendBridgeBundle')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
