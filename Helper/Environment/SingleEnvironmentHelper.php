<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Environment;

final class SingleEnvironmentHelper implements EnvironmentHelperInterface
{
    private Environment $environment;
    private string $name;
    private string $locale;

    public function __construct(string $name, Environment $environment, string $locale)
    {
        $this->environment = $environment;
        $this->name = $name;
        $this->locale = $locale;
    }

    public function getEnvironments(): array
    {
        return [$this->environment];
    }

    public function getBackend(): ?string
    {
        return null;
    }

    public function getEnvironment(): ?string
    {
        return $this->name;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
