<?php

namespace EMS\ClientHelperBundle\EMSLanguageSelectionBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('ems_language_selection');
        $rootNode
            ->children()
                ->arrayNode('supported_locale')
                    ->prototype('array')
                    ->cannotBeEmpty()
                        ->children()
                            ->scalarNode('locale')->cannotBeEmpty()->end()
                            ->scalarNode('logo_path')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('option_type')
                    ->cannotBeEmpty()
                    ->info('elasticsearch document type for the language options')
                ->end()
                ->scalarNode('emsch_trans_domain')
                    ->cannotBeEmpty()
                    ->info('translation domain for the emsch twig translation filter')
                ->end()
                ->scalarNode('ems_client')
                    ->cannotBeEmpty()
                    ->info('elasticms client defined in EMSBackendBridgeBundle')
                ->end()
                ->arrayNode('ems_hosts')
                    ->info('elasticsearch hosts')
                    ->isRequired()
                    ->prototype('scalar')->end()
                ->end()
                    ->scalarNode('ems_index_prefix')
                    ->info("example: 'test_'")
                    ->isRequired()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
