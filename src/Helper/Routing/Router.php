<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\Routing\RouteCollection;

final class Router extends BaseRouter
{
    private RoutingBuilder $routeBuilder;

    public function __construct(RoutingBuilder $routeBuilder)
    {
        $this->routeBuilder = $routeBuilder;
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->routeBuilder->buildRouteCollection();
    }
}
