<?php

namespace EMS\ClientHelperBundle\Helper\Environment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Environment
{
    private string $name;
    private ?string $regex;
    private ?string $backend;

    /** @var array<string, mixed> */
    private array $request = [];
    private string $baseUrl;
    private string $routePrefix;
    /** @var array<mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(string $name, array $config)
    {
        if (isset($config['index'])) {
            throw new \RuntimeException('Index environment attribute has been deprecated and must be removed: Environment name === Elasticsearch alias name');
        }
        $this->name = $name;

        $this->regex = $config['regex'] ?? null;
        $this->baseUrl = $config['base_url'] ?? '';
        $this->routePrefix = $config['route_prefix'] ?? '';
        $this->backend = $config['backend'] ?? false;
        $this->request = $config['request'] ?? [];
        $this->options = $config;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function matchRequest(Request $request): bool
    {
        if (null === $this->regex) {
            return true;
        }

        if (\strlen($this->baseUrl) > 0) {
            $url = \vsprintf('%s://%s%s%s', [$request->getScheme(), $request->getHttpHost(), $request->getBasePath(), $request->getPathInfo()]);
        } else {
            $url = \vsprintf('%s://%s%s', [$request->getScheme(), $request->getHttpHost(), $request->getBasePath()]);
        }

        return 1 === \preg_match($this->regex, $url);
    }

    public function modifyRequest(Request $request): void
    {
        $request->attributes->set('_environment', $this->name);
        $request->attributes->set('_backend', $this->backend);

        foreach ($this->request as $key => $value) {
            $request->attributes->set($key, $value);
            if ('_locale' === $key) {
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
