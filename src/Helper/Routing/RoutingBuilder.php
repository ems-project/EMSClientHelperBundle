<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Symfony\Component\Routing\RouteCollection;

final class RoutingBuilder extends AbstractBuilder
{
    public function buildRouteCollection(Environment $environment): RouteCollection
    {
        $routeCollection = new RouteCollection();

        if (null === $contentType = $this->clientRequest->getRouteContentType($environment)) {
            return $routeCollection;
        }

        foreach ($this->getRoutes($contentType) as $route) {
            $route->addToCollection($routeCollection);
        }

        return $routeCollection;
    }

    /**
     * @return RouteConfig[]
     */
    public function buildRouteConfigs(Environment $environment): array
    {
        if (null === $contentType = $this->clientRequest->getRouteContentType($environment)) {
            return [];
        }

        return \array_map(fn (array $hit) => RouteConfig::fromHit($hit), $this->searchRoutes($contentType));
    }

    /**
     * @return Route[]
     */
    private function getRoutes(ContentType $contentType): array
    {
        if (null !== $cache = $contentType->getCache()) {
            return $cache;
        }

        $routes = $this->createRoutes($contentType);
        $contentType->setCache($routes);
        $this->clientRequest->cacheContentType($contentType);

        return $routes;
    }

    /**
     * @return Route[]
     */
    private function createRoutes(ContentType $contentType): array
    {
        $routes = [];
        $environmentOptions = [];

        if (null !== $routePrefix = $contentType->getEnvironment()->getRoutePrefix()) {
            $environmentOptions['prefix'] = $routePrefix;
        }

        $hits = $this->searchRoutes($contentType);

        foreach ($hits as $hit) {
            $routeConfig = RouteConfig::fromHit($hit);
            $options = \array_merge($routeConfig->getOptions(), $environmentOptions);
            $routes[] = new Route($routeConfig->getName(), $options);
        }

        return $routes;
    }

    /**
     * @return array<mixed>
     */
    private function searchRoutes(ContentType $contentType): array
    {
        return $this->search($contentType, [
            'sort' => [
                'order' => [
                    'order' => 'asc',
                    'missing' => '_last',
                    'unmapped_type' => 'long',
                ],
            ],
        ]);
    }
}
