<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Environment;

use Symfony\Component\HttpFoundation\RequestStack;

final class EnvironmentHelper
{
    /** @var Environment[] */
    private array $environments = [];
    private RequestStack $requestStack;
    private string $emschEnv;

    /**
     * @param array<string, array> $environments
     */
    public function __construct(RequestStack $requestStack, string $emschEnv, array $environments)
    {
        $this->requestStack = $requestStack;
        $this->emschEnv = $emschEnv;

        foreach ($environments as $name => $config) {
            $this->environments[$name] = new Environment($name, $config);
        }
    }

    public function addEnvironment(Environment $environment): void
    {
        $this->environments[] = $environment;
    }

    public function getEmschEnv(): string
    {
        return $this->emschEnv;
    }

    public function getEnvironment(string $name): ?Environment
    {
        return $this->environments[$name] ?? null;
    }

    /**
     * @return Environment[]
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    public function getBackend(): ?string
    {
        $current = $this->requestStack->getCurrentRequest();

        return null !== $current ? $current->get(Environment::BACKEND_ATTRIBUTE) : null;
    }

    public function getLocale(): string
    {
        $current = $this->requestStack->getCurrentRequest();
        if (null === $current) {
            throw new \RuntimeException('Unexpected null request');
        }

        return $current->getLocale();
    }

    public function getCurrentEnvironment(): ?Environment
    {
        if ('cli' === PHP_SAPI) {
            return $this->environments[$this->emschEnv] ?? null;
        }

        foreach ($this->environments as $environment) {
            if ($environment->isActive()) {
                return $environment;
            }
        }

        return null;
    }
}
