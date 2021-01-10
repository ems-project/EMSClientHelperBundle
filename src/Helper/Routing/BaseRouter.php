<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

abstract class BaseRouter implements RouterInterface, RequestMatcherInterface
{
    /**
     * @var RequestContext
     */
    protected $context;

    /**
     * @var RouteCollection
     */
    protected $collection;

    /**
     * @var UrlMatcher
     */
    protected $matcher;

    /**
     * @var UrlGenerator
     */
    protected $generator;

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathInfo)
    {
        return $this->getMatcher()->match($pathInfo);
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        return $this->getMatcher()->matchRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    private function getMatcher(): UrlMatcher
    {
        if (null === $this->matcher) {
            $this->matcher = new UrlMatcher($this->getRouteCollection(), $this->getContext());
        }

        return $this->matcher;
    }

    private function getGenerator(): UrlGenerator
    {
        if (null === $this->generator) {
            $this->generator = new UrlGenerator($this->getRouteCollection(), $this->getContext());
        }

        return $this->generator;
    }
}
