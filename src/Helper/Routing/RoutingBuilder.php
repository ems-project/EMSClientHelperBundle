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
     * @return array<mixed>
     */
    public function buildRouteConfigs(Environment $environment): array
    {
        if (null === $contentType = $this->clientRequest->getRouteContentType($environment)) {
            return [];
        }

        $routeConfigs = [];

        foreach ($this->searchRoutes($contentType) as $hit) {
            $routeConfig = RouteConfig::fromHit($hit);
            $routeConfigs[$routeConfig->getName()] = $routeConfig->toArray();
        }

        return $routeConfigs;
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
        $search = $this->clientRequest->search($contentType->getName(), [
            'sort' => [
                'order' => [
                    'order' => 'asc',
                    'missing' => '_last',
                    'unmapped_type' => 'long',
                ],
            ],
        ], 0, 1000, [], $contentType->getEnvironment()->getAlias());

        $total = $search['hits']['total']['value'] ?? $search['hits']['total'];

        if ($total > 1000) {
            $this->logger->error('Only the first 1000 routes have been loaded on a total of {total}', ['total' => $total]);
        }

        return $search['hits']['hits'];
    }
}
