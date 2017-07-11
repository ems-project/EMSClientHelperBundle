<?php

namespace EMS\ClientHelperBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Environment Loader
 */
class EnvironmentLoader extends Loader
{
    private $loaded = false;

    /**
     * @param string $resource example: environment|@AppBundle/Controller/
     * @param string $type     ems_environment
     *
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        list($environment, $resource) = preg_split('/\|/', $resource);
        
        /* @var $routes RouteCollection */
        $routes = $this->import($resource, 'annotation');
        
        $collection = new RouteCollection();
        
        foreach ($routes as $name => $route) {
            /* @var $route \Symfony\Component\Routing\Route */
            $route->setDefault('_environment', $environment);
            $collection->add($environment.'_'.$name, $route);
        }
        
        $this->loaded = true;
        
        return $collection;
    }
    
    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'emsch_environment' === $type;
    }
}
