<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

final class Router extends BaseRouter
{
    private ClientRequestManager $manager;
    private LoggerInterface $logger;
    /** @var string[] */
    private array $locales;

    private bool $hasBuild = false;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, array $locales)
    {
        $this->manager = $manager;
        $this->logger = $manager->getLogger();
        $this->locales = $locales;
    }

    public function getRouteCollection(): RouteCollection
    {
        if (!$this->hasBuild) {
            $this->buildRouteCollection();
        }

        return $this->collection;
    }

    public function buildRouteCollection(): void
    {
        $collection = new RouteCollection();
        $this->addEMSRoutes($collection);

        $this->collection = $collection;
        $this->hasBuild = true;
    }

    private function addEMSRoutes(RouteCollection $collection): void
    {
        foreach ($this->manager->all() as $clientRequest) {
            if (!$clientRequest->hasOption('route_type')) {
                continue;
            }

            if (!$clientRequest->mustBeBind() && !$clientRequest->hasEnvironments()) {
                continue;
            }

            $routes = $this->getRoutes($clientRequest);

            foreach ($routes as $route) {
                $route->addToCollection($collection, $this->locales);
            }
        }
    }

    /**
     * @return Route[]
     */
    private function getRoutes(ClientRequest $clientRequest): array
    {
        if ($clientRequest->isBind()) {
            return $this->getRoutesByEnvironment($clientRequest, $clientRequest->getCurrentEnvironment());
        }

        $routes = [];
        foreach ($clientRequest->getEnvironments() as $environment) {
            if (\strlen($environment->getRoutePrefix()) > 0) {
                $routes = \array_merge($routes, $this->getRoutesByEnvironment($clientRequest, $environment));
            }
        }

        return $routes;
    }

    /**
     * @return Route[]
     */
    private function getRoutesByEnvironment(ClientRequest $clientRequest, Environment $environment): array
    {
        if (null === $contentType = $clientRequest->getRouteContentType($environment)) {
            return [];
        }

        if (null !== $cache = $contentType->getCache()) {
            return $cache;
        }

        try {
            $routes = $this->createRoutes($clientRequest, $environment, $contentType->getName());
            $contentType->setCache($routes);
            $clientRequest->cacheContentType($contentType);

            return $routes;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return Route[]
     */
    private function createRoutes(ClientRequest $clientRequest, Environment $environment, string $type): array
    {
        $baseUrl = $environment->getBaseUrl();
        $routePrefix = $environment->getRoutePrefix();
        $routes = [];

        $search = $clientRequest->search($type, [
            'sort' => [
                'order' => [
                    'order' => 'asc',
                    'missing' => '_last',
                    'unmapped_type' => 'long',
                ],
            ],
        ], 0, 1000, [], null, $environment->getAlias());

        $total = $search['hits']['total']['value'] ?? $search['hits']['total'];
        if ($total > 1000) {
            $this->logger->error('Only the first 1000 routes have been loaded on a total of {total}', ['total' => $total]);
        }

        foreach ($search['hits']['hits'] as $hit) {
            $source = $hit['_source'];
            $name = $routePrefix.$source['name'];

            try {
                $options = \json_decode($source['config'], true);

                if (JSON_ERROR_NONE !== \json_last_error()) {
                    throw new \InvalidArgumentException(\sprintf('invalid json %s', $source['config']));
                }

                $options['query'] = $source['query'] ?? null;

                $staticTemplate = isset($source['template_static']) ? '@EMSCH/'.$source['template_static'] : null;
                $options['template'] = $source['template_source'] ?? $staticTemplate;
                $options['index_regex'] = $source['index_regex'] ?? null;

                if (\strlen($baseUrl) > 0) {
                    $options['path'] = $this->prependBaseUrl($options['path'] ?? null, $baseUrl);
                }

                $routes[] = new Route($name, $options);
            } catch (\Throwable $e) {
                $this->logger->error('Router failed to create ems route {name} : {error}', ['name' => $name, 'error' => $e->getMessage()]);
            }
        }

        return $routes;
    }

    /**
     * @param array<string, string>|string|null $path
     *
     * @return array<string, string>|string
     */
    private function prependBaseUrl($path, string $baseUrl)
    {
        if (\is_array($path)) {
            foreach ($path as $locale => $subPath) {
                $path[$locale] = \sprintf('%s/%s', $baseUrl, '/' === \substr($subPath, 0, 1) ? \substr($subPath, 1) : $subPath);
            }
        } elseif (\is_string($path)) {
            $path = \sprintf('%s/%s', $baseUrl, '/' === \substr($path, 0, 1) ? \substr($path, 1) : $path);
        } else {
            throw new \RuntimeException('Unexpected path type');
        }

        return $path;
    }
}
