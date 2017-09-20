<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('ems_twig_list');
        $rootNode
            ->children()
                ->scalarNode('client_request')->cannotBeEmpty()->end()
                ->booleanNode('twig_cache')->defaultTrue()->end()
                ->arrayNode('paths')
                    ->prototype('array')
                    ->cannotBeEmpty()
                        ->children()
                            ->scalarNode('regex')->cannotBeEmpty()->end()
                            ->scalarNode('path')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
