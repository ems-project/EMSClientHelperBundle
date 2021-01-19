<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Environment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class Environment
{
    const ENVIRONMENT_ATTRIBUTE = '_environment';
    const BACKEND_ATTRIBUTE = '_backend';
    const LOCALE_ATTRIBUTE = '_locale';
    const REGEX_CONFIG = 'regex';
    const BASE_URL = 'base_url';
    const BACKEND_CONFIG = 'backend';
    const REQUEST_CONFIG = 'request';
    const ALIAS_CONFIG = 'alias';
    private string $name;
    private string $alias;
    private ?string $regex;
    private ?string $backend;

    /** @var array<string, mixed> */
    private array $request = [];
    private string $baseUrl;
    /** @var array<mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->alias = $config[self::ALIAS_CONFIG] ?? $name;
        $this->regex = $config[self::REGEX_CONFIG] ?? null;
        $this->baseUrl = $config[self::BASE_URL] ?? '';
        $this->backend = $config[self::BACKEND_CONFIG] ?? null;
        $this->request = $config[self::REQUEST_CONFIG] ?? [];
        $this->options = $config;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
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
