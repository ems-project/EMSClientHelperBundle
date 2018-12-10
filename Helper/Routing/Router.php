<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Cache\CacheHelper;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

class Router extends BaseRouter
{
    /** @var ClientRequestManager */
    private $manager;
    /** @var LoggerInterface */
    private $logger;
    /** @var CacheHelper */
    private $cache;
    /** @var array */
    private $locales;
    /** @var array */
    private $templates;
    /** @var array */
    private $routes;

    public function __construct(ClientRequestManager $manager, CacheHelper $cacheHelper, array $locales, array $templates, array $routes)
    {
        $this->manager = $manager;
        $this->logger = $manager->getLogger();
        $this->cache = $cacheHelper;
        $this->locales = $locales;
        $this->templates = $templates;
        $this->routes = $routes;
    }

    public function getRouteCollection(): RouteCollection
    {
        if (!$this->collection) {
            $this->buildRouteCollection();
        }

        return $this->collection;
    }

    public function buildRouteCollection(): void
    {
        $collection = new RouteCollection();
        $this->addEMSRoutes($collection);
        $this->addEnvRoutes($collection);
        $this->addLanguageSelectionRoute($collection);
        $this->addSearchResultRoute($collection);

        $this->collection = $collection;
    }

    private function addEnvRoutes(RouteCollection $collection): void
    {
        foreach ($this->routes as $name => $options) {
            $route = new Route('ems_'.$name, $options);
            $route->addToCollection($collection, $this->locales);
        }
    }

    private function addLanguageSelectionRoute(RouteCollection $collection): void
    {
        if (isset($this->templates['language']) && count($this->locales) > 1) {
            $langSelectRoute = new Route('emsch_language_selection', [
                'path' => '/language-selection',
                'controller' => 'emsch.controller.language_select::view',
                'defaults' => ['template' => $this->templates['language']]
            ]);
            $langSelectRoute->addToCollection($collection);
        }
    }

    private function addSearchResultRoute(RouteCollection $collection): void
    {
        if (isset($this->templates['search'])) {
            $searchRoute = new Route('emsch_search', [
                'path' => '/{_locale}/search',
                'controller' => 'emsch.controller.search::results',
                'defaults' => ['template' => $this->templates['search']]
            ]);
            $searchRoute->addToCollection($collection);
        }
    }

    private function addEMSRoutes(RouteCollection $collection): void
    {
        foreach ($this->manager->all() as $clientRequest) {
            if (!$clientRequest->hasOption('route_type')) {
                continue;
            }

            $routes = $this->getRoutes($clientRequest);

            foreach ($routes as $route) {
                /** @var $route Route */
                $route->addToCollection($collection, $this->locales);
            }
        }
    }

    private function getRoutes(ClientRequest $clientRequest): array
    {
        $cacheItem = $this->cache->get($clientRequest->getCacheKey('routes'));

        $type = $clientRequest->getOption('[route_type]');
        $lastChanged = $clientRequest->getLastChangeDate($type);

        if ($this->cache->isValid($cacheItem, $lastChanged)) {
            return $this->cache->getData($cacheItem);
        }

        $routes = $this->createRoutes($clientRequest, $type);
        $this->cache->save($cacheItem, $routes);

        return $routes;
    }

    private function createRoutes(ClientRequest $clientRequest, string $type): array
    {
        $routes = [];
        $scroll = $clientRequest->scrollAll([
            'size' => 100,
            'type' => $type,
            'sort' => ['order']
        ], '5s');

        foreach ($scroll as $hit) {
            try {
                $source = $hit['_source'];
                $options = json_decode($source['config'], true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException(sprintf('invalid json %s', $source['config']));
                }

                $options['query'] = $source['query'] ?? null;

                $staticTemplate = isset($source['template_static']) ? '@EMSCH/'.$source['template_static'] : null;
                $options['template'] = $source['template_source'] ?? $staticTemplate;

                $routes[] = new Route($source['name'], $options);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Router failed to create ems route (%s)', $e->getMessage()));
            }
        }

        return $routes;
    }
}