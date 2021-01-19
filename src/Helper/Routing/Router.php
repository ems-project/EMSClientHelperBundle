<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

final class Router extends BaseRouter
{
    private ClientRequest $clientRequest;
    private LoggerInterface $logger;
    /** @var string[] */
    private array $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, array $locales)
    {
        $this->clientRequest = $manager->getDefault();
        $this->logger = $manager->getLogger();
        $this->locales = $locales;
    }

    public function getRouteCollection(): RouteCollection
    {
        $routeCollection = new RouteCollection();

        if (null === $contentType = $this->clientRequest->getRouteContentType()) {
            return $routeCollection;
        }

        foreach ($this->getRoutes($contentType) as $route) {
            $route->addToCollection($routeCollection);
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

        $routes = $this->createRoutes($contentType->getName());
        $contentType->setCache($routes);
        $this->clientRequest->cacheContentType($contentType);

        return $routes;
    }

    /**
     * @return Route[]
     */
    private function createRoutes(string $name): array
    {
        $routes = [];

        $search = $this->clientRequest->search($name, [
            'sort' => [
                'order' => [
                    'order' => 'asc',
                    'missing' => '_last',
                    'unmapped_type' => 'long',
                ],
            ],
        ], 0, 1000);

        $total = $search['hits']['total']['value'] ?? $search['hits']['total'];
        if ($total > 1000) {
            $this->logger->error('Only the first 1000 routes have been loaded on a total of {total}', ['total' => $total]);
        }

        foreach ($search['hits']['hits'] as $hit) {
            $source = $hit['_source'];
            $name = $source['name'];

            try {
                $options = \json_decode($source['config'], true);

                if (JSON_ERROR_NONE !== \json_last_error()) {
                    throw new \InvalidArgumentException(\sprintf('invalid json %s', $source['config']));
                }

                $options['query'] = $source['query'] ?? null;

                $staticTemplate = isset($source['template_static']) ? '@EMSCH/'.$source['template_static'] : null;
                $options['template'] = $source['template_source'] ?? $staticTemplate;
                $options['index_regex'] = $source['index_regex'] ?? null;

                $routes[] = new Route($name, $options);
            } catch (\Throwable $e) {
                $this->logger->error('Router failed to create ems route {name} : {error}', ['name' => $name, 'error' => $e->getMessage()]);
            }
        }

        return $routes;
    }
}
