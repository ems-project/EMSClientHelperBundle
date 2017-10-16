<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
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
                            ->end()
                        ->end()
                    ->end()
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
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        

        return $treeBuilder;
    }
}
