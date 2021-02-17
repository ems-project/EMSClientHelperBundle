<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Environment;

use EMS\CommonBundle\Common\Json;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class Environment
{
    const ENVIRONMENT_ATTRIBUTE = '_environment';
    const BACKEND_ATTRIBUTE = '_backend';
    const LOCALE_ATTRIBUTE = '_locale';
    const REGEX_CONFIG = 'regex';
    const ROUTE_PREFIX = 'route_prefix';
    const BACKEND_CONFIG = 'backend';
    const REQUEST_CONFIG = 'request';
    const ALIAS_CONFIG = 'alias';

    private string $name;
    private bool $active = false;
    private string $alias;
    private ?string $regex;
    private ?string $routePrefix;
    private ?string $backend;

    /** @var array<string, mixed> */
    private array $request = [];
    /** @var array<mixed> */
    private array $options;
    private string $hash;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->alias = $config[self::ALIAS_CONFIG] ?? $name;
        $this->regex = $config[self::REGEX_CONFIG] ?? null;
        $this->routePrefix = $config[self::ROUTE_PREFIX] ?? null;
        $this->backend = $config[self::BACKEND_CONFIG] ?? null;
        $this->request = $config[self::REQUEST_CONFIG] ?? [];
        $this->options = $config;
        $this->hash = $name.\sha1(Json::encode($config));
    }

    public function getBackendUrl(): ?string
    {
        return $this->options[self::BACKEND_CONFIG] ?? null;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getRoutePrefix(): ?string
    {
        return $this->routePrefix;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function makeActive(): void
    {
        $this->active = true;
    }

    public function matchRequest(Request $request): bool
    {
        if (null !== $this->routePrefix) {
            $requestPrefix = \substr($request->getPathInfo(), 0, \strlen($this->routePrefix));
            if ($requestPrefix === $this->routePrefix) {
                return true;
            }
        }

        if (null === $this->regex) {
            return false;
        }

        $url = \vsprintf('%s://%s%s', [$request->getScheme(), $request->getHttpHost(), $request->getBasePath()]);

        return 1 === \preg_match($this->regex, $url);
    }

    public function modifyRequest(Request $request): void
    {
        $request->attributes->set(self::ENVIRONMENT_ATTRIBUTE, $this->name);
        $request->attributes->set(self::BACKEND_ATTRIBUTE, $this->backend);

        foreach ($this->request as $key => $value) {
            if (self::ENVIRONMENT_ATTRIBUTE === $key) {
                continue;
            }

            $request->attributes->set($key, $value);
            if (self::LOCALE_ATTRIBUTE === $key) {
                $request->setLocale($value);
            }
        }
    }

    /**
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getOption(string $propertyPath, $default = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (!$propertyAccessor->isReadable($this->options, $propertyPath)) {
            return $default;
        }

        return $propertyAccessor->getValue($this->options, $propertyPath);
    }

    public function hasOption(string $option): bool
    {
        return isset($this->options[$option]) && null !== $this->options[$option];
    }
}
