<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        /* @var $rootNode ArrayNodeDefinition */
        $rootNode = $treeBuilder->root('ems_client_helper');

        $rootNode
            ->children()
                ->variableNode('request_environments')->isRequired()->end()
                ->variableNode('locales')->isRequired()->end()
                ->booleanNode('bind_locale')->end()
                ->scalarNode('etag_hash_algo')->end()
                ->booleanNode('dump_assets')
                    ->setDeprecated('The ems_client_helper "%node%" option is deprecated. Will be removed!')
                    ->isRequired()
                    ->defaultTrue()
                ->end()
                ->arrayNode('templates')
                    ->children()
                        ->scalarNode('error')->end()
                        ->scalarNode('ems_link')->end()
                    ->end()
                ->end()

            ->end()
        ;

        $this->addElasticmsSection($rootNode);
        $this->addApiSection($rootNode);
        $this->addRoutingSelection($rootNode);
        $this->addUserApiSection($rootNode);

        return $treeBuilder;
    }

    private function addElasticmsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('elasticms')
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(function ($v) {
                            if (1 === \count($v)) {
                                $v[\key($v)]['default'] = true;

                                return $v;
                            }

                            $default = [];

                            foreach ($v as $name => $options) {
                                if (isset($options['default']) && $options['default']) {
                                    $default[] = $name;
                                }
                            }

                            if (empty($default)) {
                                throw new \InvalidArgumentException('no default elasticms configured');
                            }

                            if (\count($default) > 1) {
                                throw new \InvalidArgumentException('there can only be 1 default elasticms');
                            }

                            return $v;
                        })
                    ->end()
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
                            ->booleanNode('default')->end()
                            ->booleanNode('must_be_bind')->defaultValue(true)->end()
                            ->scalarNode('translation_type')
                                ->info("example: 'test_i18n'")
                                ->defaultValue(null)
                            ->end()
                            ->scalarNode('route_type')
                                ->defaultValue(null)
                            ->end()
                            ->scalarNode('asset_config_type')
                                ->defaultValue(null)
                            ->end()
                            ->variableNode('templates')
                                ->example('{"template": {"name": "key","code": "body"}}')
                            ->end()
                            ->variableNode('api')
                                ->info('api for content exposing')
                                ->example('{"enable": true, "name": "api"}')
                            ->end()
                            ->variableNode('search_config')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addApiSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('api')
                    ->prototype('array')
                        ->info('name for the ems-project')
                        ->children()
                            ->scalarNode('url')
                                ->info('url of the elasticms without /api')
                                ->isRequired()
                            ->end()
                            ->scalarNode('key')
                                ->info('api key')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addRoutingSelection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('routing')
                ->canBeEnabled()
                ->children()
                    ->scalarNode('client_request')
                        ->isRequired()
                        ->beforeNormalization()
                            ->always(function ($v) {
                                return 'emsch.client_request.'.$v;
                            })
                        ->end()
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
                ->end()
            ->end()
        ;
    }

    private function addUserApiSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('user_api')
                    ->canBeEnabled()
                        ->children()
                            ->scalarNode('url')
                                ->info('url of the elasticms without /user_api')
                                ->isRequired()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
