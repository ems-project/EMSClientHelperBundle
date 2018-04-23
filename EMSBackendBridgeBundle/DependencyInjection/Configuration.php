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
                            ->arrayNode('hosts')
                                ->info('elasticsearch hosts')
                                ->isRequired()
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('index_prefix')
                                ->info("example: 'test_'")
                                ->defaultValue(null)
                            ->end()
                            ->scalarNode('translation_type')
                                ->info("example: 'test_i18n'")
                                ->defaultValue(null)
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
}
