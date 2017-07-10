<?php

namespace EMS\ClientHelperBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('ems_client_helper');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        
        $rootNode
         ->children()
                ->arrayNode('elasticms')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('index_prefix')->isRequired()->end()
                            ->arrayNode('clusters')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        	->arrayNode('twig_list')
	          ->children()
	         	->arrayNode('templates')
	   			  ->prototype('array')
	   			  	->cannotBeEmpty()
	             	->children()
	              	  ->scalarNode('resource')->cannotBeEmpty()->end()
	              	  ->scalarNode('base_path')->cannotBeEmpty()->end() 
	        	  ->end()
	        	->end()
	        	->defaultValue($this->getTemplatesDefaultValues())
	          ->end()
	          ->booleanNode('app_enabled')->defaultFalse()->end()
	          ->arrayNode('app_base_path')->prototype('scalar')->end() 
	        ->end()
       	  ->end()
        ->end();
        

        return $treeBuilder;
    }
    
    /**
     * Returns default values for profile menu (to avoid BC Break)
     *
     * @return array
     */
    protected function getTemplatesDefaultValues()
    {
    	return array(
    			array(
    					'resource'  => 'EMSClientHelperBundle',
    					'base_path'  => 'Resources/views/Pages',
    			),
    	);
    }
}
