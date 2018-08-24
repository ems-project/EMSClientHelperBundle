<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

class Router implements RouterInterface, RequestMatcherInterface
{
    /**
     * @var array
     */
    private $routes;

    /**
     * @var RequestContext
     */
    private $context;

    /**
     * @var RouteCollection
     */
    private $collection;

    /**
     * @var UrlMatcher
     */
    private $matcher;

    /**
     * @var UrlGenerator
     */
    private $generator;

    /**
     * @var array
     */
    private $locales;

    /**
     * @param array $routes
     * @param array $locales
     */
    public function __construct(array $routes, array $locales)
    {
        $this->routes = $routes;
        $this->locales = $locales;
    }

    /**
     * @inheritdoc
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @inheritdoc
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
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
     * @inheritdoc
     */
    public function match($pathinfo)
    {
        return $this->getMatcher()->match($pathinfo);
    }

    /**
     * @inheritdoc
     */
    public function matchRequest(Request $request)
    {
        return $this->getMatcher()->matchRequest($request);
    }

    /**
     * @inheritdoc
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    /**
     * @return UrlMatcher
     */
    private function getMatcher(): UrlMatcher
    {
        if (null === $this->matcher) {
            $this->matcher = new UrlMatcher($this->getRouteCollection(), $this->getContext());
        }

        return $this->matcher;
    }

    /**
     * @return UrlGenerator
     */
    private function getGenerator(): UrlGenerator
    {
        if (null === $this->generator) {
            $this->generator = new UrlGenerator($this->getRouteCollection(), $this->getContext());
        }

        return $this->generator;
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
            $collection->add($config->getName(), $config->getRoute());
        }

        if (count($this->locales) > 1) {
            $langSelectConfig = new RouteConfig('language_selection', [
                'path' => '/language-selection',
                'controller' => 'emsch.controller.language_select::view'
            ]);
            $collection->add($langSelectConfig->getName(), $langSelectConfig->getRoute());
        }

        $searchConfig = new RouteConfig('search', [
            'path' => '/search',
            'controller' => 'emsch.controller.search::results'
        ]);
        $collection->add($searchConfig->getName(), $searchConfig->getRoute());

        return $collection;
    }
}