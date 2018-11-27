<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\Routing\RouteCollection;

class EMSRouter extends BaseRouter
{
    /**
     * @var array
     */
    private $routes;

    /**
     * @param array $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @inheritdoc
     */
    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = $this->buildCollection();
        }

        return $this->collection;
    }

    /**
     * @return RouteCollection
     */
    private function buildCollection(): RouteCollection
    {
        $configs = array_map(function (string $name, array $options) {
            return new RouteConfig($name, $options, true);
        }, array_keys($this->routes), $this->routes);

        $collection = new RouteCollection();

        foreach ($configs as $config) {
            /* @var $config RouteConfig */
            $routes = $config->getRoutes();

            foreach ($routes as $name => $route) {
                $collection->add($name, $route);
            }
        }

        return $collection;
    }
}