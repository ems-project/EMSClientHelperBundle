<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

final class RoutingBuilder
{
    private ClientRequest $clientRequest;
    private LoggerInterface $logger;
    private RouteFactory $routeFactory;

    public function __construct(ClientRequestManager $manager, RouteFactory $routeFactory, LoggerInterface $logger)
    {
        $this->clientRequest = $manager->getDefault();
        $this->logger = $logger;
        $this->routeFactory = $routeFactory;
    }

    public function buildRouteCollection(Environment $environment): RouteCollection
    {
        $routeCollection = new RouteCollection();

        if (null === $contentType = $this->clientRequest->getRouteContentType($environment)) {
            return $routeCollection;
        }

        foreach ($this->getRoutes($contentType) as $route) {
            $route->addToCollection($routeCollection);
        }

        if (null !== $routePrefix = $contentType->getEnvironment()->getRoutePrefix()) {
            $routeCollection->addPrefix($routePrefix);
        }

        return $routeCollection;
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

        foreach ($search['hits']['hits'] as $hit) {
            if (null !== $route = $this->routeFactory->fromHit($hit)) {
                $routes[] = $route;
            }
        }

        return $routes;
    }
}
