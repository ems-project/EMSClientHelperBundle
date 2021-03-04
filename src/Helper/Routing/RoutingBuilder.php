<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\Builder\BuilderDocumentInterface;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\CommonBundle\Search\Search;
use Symfony\Component\Routing\RouteCollection;

final class RoutingBuilder extends AbstractBuilder
{
    public function buildRouteCollection(Environment $environment): RouteCollection
    {
        $routeCollection = new RouteCollection();

        if (null === $contentType = $this->getContentType($environment)) {
            return $routeCollection;
        }

        if ($environment->isLocalPulled()) {
            $routes = $environment->getLocal()->getRouting()->createRoutes();
        } else {
            $routes = $this->createRoutes($contentType);
        }

        $routePrefix = $contentType->getEnvironment()->getRoutePrefix();
        foreach ($routes as $route) {
            $route->addToCollection($routeCollection, $this->locales, $routePrefix);
        }

        return $routeCollection;
    }

    public function buildFiles(Environment $environment, string $directory): RoutingFile
    {
        return RoutingFile::build($directory, $this->getDocuments($environment));
    }

    public function getContentType(Environment $environment): ?ContentType
    {
        return $this->clientRequest->getRouteContentType($environment);
    }

    /**
     * @return BuilderDocumentInterface[]|RoutingDocument[]
     */
    public function getDocuments(Environment $environment): array
    {
        if (null === $contentType = $this->getContentType($environment)) {
            return [];
        }

        return $this->searchDocuments($contentType);
    }

    protected function modifySearch(Search $search): void
    {
        $search->setSort(['order' => ['order' => 'asc', 'missing' => '_last', 'unmapped_type' => 'long']]);
    }

    /**
     * @return Route[]
     */
    private function createRoutes(ContentType $contentType): array
    {
        if (null !== $cache = $contentType->getCache()) {
            return $cache;
        }

        $routes = [];
        foreach ($this->searchDocuments($contentType) as $document) {
            $routes[] = Route::fromData($document->getName(), $document->getRouteData());
        }

        $contentType->setCache($routes);
        $this->clientRequest->cacheContentType($contentType);

        return $routes;
    }

    /**
     * @return RoutingDocument[]
     */
    private function searchDocuments(ContentType $contentType): array
    {
        $documents = [];

        foreach ($this->search($contentType)->getDocuments() as $document) {
            $documents[] = new RoutingDocument($document);
        }

        return $documents;
    }
}
