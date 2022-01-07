<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;

final class Router extends BaseRouter implements VersatileGeneratorInterface
{
    private EnvironmentHelper $environmentHelper;
    private RoutingBuilder $builder;

    public function __construct(EnvironmentHelper $environmentHelper, RoutingBuilder $routeBuilder)
    {
        $this->environmentHelper = $environmentHelper;
        $this->builder = $routeBuilder;
    }

    public function supports($name): bool
    {
        return 0 === \preg_match('/^(_profiler|_wdt).*$/s', $name);
    }

    /**
     * @param array<mixed> $parameters
     */
    public function getRouteDebugMessage($name, array $parameters = []): string
    {
        return (string) $name;
    }

    public function getRouteCollection(): RouteCollection
    {
        if (null === $environment = $this->environmentHelper->getCurrentEnvironment()) {
            return new RouteCollection();
        }

        return $this->builder->buildRouteCollection($environment);
    }
}
