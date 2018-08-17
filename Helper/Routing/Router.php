<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
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
     * @param array $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
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
        return null; // @todo implement generation
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
     * @return RouteCollection
     */
    private function buildCollection(): RouteCollection
    {
        $configs = array_map(function (string $name, array $options) {
            return new Config($name, $options);
        }, array_keys($this->routes), $this->routes);

        $collection = new RouteCollection();

        foreach ($configs as $config) {
            /* @var $config Config */
            $collection->add($config->getName(), $config->getRoute());
        }

        return $collection;
    }
}