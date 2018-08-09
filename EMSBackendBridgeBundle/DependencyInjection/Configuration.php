<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Helper\File\FileManager;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Helper\Routing\Link\Generator;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Helper\Routing\RedirectHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * EMS Backend Bridge Bundle Configuration
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
        $rootNode = $treeBuilder->root('ems_backend_bridge');
        
        $this->addRequestEnvironmentsSection($rootNode);
        $this->addElasticmsSection($rootNode);
        $this->addApiSection($rootNode);
        $this->addTwigListSection($rootNode);
        $this->addLanguageSelection($rootNode);
        $this->addRoutingSelection($rootNode);
        
        return $treeBuilder;
    }
    
    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addRequestEnvironmentsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('request_environments')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('regex')
                                ->defaultValue(null)
                            ->end()
                            ->scalarNode('index')
                                ->defaultValue(null)
                            ->end()
                            ->scalarNode('backend')
                                ->defaultValue(null)
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
    private function addElasticmsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('elasticms')
                    ->prototype('array')
                        ->info('name for the ems-project')
                        ->children()
                            ->variableNode('hosts')
                                ->info('elasticsearch hosts')
                                ->isRequired()
                            ->end()
                            ->scalarNode('index_prefix')
                                ->info("example: 'test_'")
                                ->defaultValue(null)
                            ->end()
                            ->scalarNode('translation_type')
                                ->info("example: 'test_i18n'")
                                ->defaultValue(null)
                            ->end()
                            ->variableNode('templates')
                                ->example('{"template": {"name": "key","code": "body"}}')
                            ->end()
                            ->variableNode('api')
                                ->info('api for content exposing')
                                ->example('{"enable": true, "name": "api"}')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('clear_cache')
                    ->info('ems-project to use for clear cache with translations')
                ->end()
            ->end()
        ;
    }
    
    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addApiSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('api')
                    ->prototype('array')
                        ->info('name for the ems-project')
                        ->children()
                            ->scalarNode('url')
                                ->info("url of the elasticms without /api")
                                ->isRequired()
                            ->end()
                            ->scalarNode('key')
                                ->info("api key")
                                ->isRequired()
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
    private function addTwigListSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('twig_list')
                    ->children()
                        ->arrayNode('templates')
                            ->defaultValue([
                                ['path'  => '@EMSBackendBridgeBundle/Resources/views/TwigList', 'namespace'  => '@EMSBackendBridgeBundle/TwigList']
                            ])
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('path')->cannotBeEmpty()->end()
                                    ->scalarNode('namespace')->defaultNull()->end()
                                ->end()
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
    private function addLanguageSelection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('language_selection')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('client_request')
                            ->isRequired()
                            ->beforeNormalization()
                                ->always(function ($v) { return 'emsch.client_request.'.$v; })
                            ->end()
                        ->end()
                        ->scalarNode('option_type')
                            ->isRequired()
                            ->info('elasticsearch document type for the language options')
                        ->end()
                        ->arrayNode('supported_locale')
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('locale')->cannotBeEmpty()->end()
                                    ->scalarNode('logo_path')->cannotBeEmpty()->end()
                                ->end()
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
    private function addRoutingSelection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('routing')
                ->canBeEnabled()
                ->children()
                    ->scalarNode('client_request')
                        ->isRequired()
                        ->beforeNormalization()
                            ->always(function ($v) { return 'emsch.client_request.'.$v; })
                        ->end()
                    ->end()
                    ->scalarNode('redirect_type')
                        ->isRequired()
                        ->info('content type used to define redirection')
                    ->end()
                    ->arrayNode('relative_paths')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('regex')
                                    ->info('regex for matching the content_type')
                                    ->isRequired()
                                ->end()
                                ->scalarNode('path')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
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
            ->end()
        ;
    }
}
