<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\File;

use EMS\ClientHelperBundle\Helper\Routing\RouteConfig;
use EMS\CommonBundle\Common\Json;

final class RoutesFile
{
    private TemplatesFile $templatesFile;
    /** @var RouteConfig[] */
    private array $routeConfigs = [];

    public function __construct(TemplatesFile $templatesFile)
    {
        $this->templatesFile = $templatesFile;
    }

    public static function fromJson(TemplatesFile $templatesFile, string $filePath): self
    {
        $routesFile = new self($templatesFile);

        $decoded = Json::decode(\file_get_contents($filePath));
        foreach ($decoded as $name => $options) {
            $routesFile->addRouteConfig(RouteConfig::fromArray($name, $options));
        }

        return $routesFile;
    }

    /**
     * @return RouteConfig[]
     */
    public function getRouteConfigs(): array
    {
        return $this->routeConfigs;
    }

    public function addRouteConfig(RouteConfig $routeConfig): void
    {
        $this->routeConfigs[] = $routeConfig;
    }

    public function toJson(): string
    {
        $routes = [];

        foreach ($this->routeConfigs as $routeConfig) {
            $route = $routeConfig->toArray();

            if (isset($route['template_static'])) {
                $route['template_static'] = $this->templatesFile->getName($route['template_static']);
            }

            $routes[$routeConfig->getName()] = $route;
        }

        return Json::encode($routes, true);
    }
}
