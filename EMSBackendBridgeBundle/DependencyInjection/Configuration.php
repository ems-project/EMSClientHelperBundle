<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection;

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
                            ->arrayNode('templates')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')
                                            ->defaultValue('name')
                                        ->end()
                                        ->scalarNode('code')
                                            ->defaultValue('body')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('api')
                                ->canBeEnabled()
                                ->info('api for content exposing')
                                ->children()
                                    ->scalarNode('name')
                                        ->isRequired()
                                    ->end()
                                    ->booleanNode('protected')
                                        ->defaultTrue()
                                    ->end()
                                ->end()
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
                                ->info("url of the elasticms withoud /api")
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
}
