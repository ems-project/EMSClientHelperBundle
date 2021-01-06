<?php

namespace EMS\ClientHelperBundle\Helper\Environment;

use EMS\ClientHelperBundle\Exception\EnvironmentNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

class EnvironmentHelper
{
    /** @var Environment[] */
    private $environments = [];
    /** @var RequestStack */
    private $requestStack;
    /** @var string */
    private $emschEnv;

    /**
     * @param array<string, array> $environments
     */
    public function __construct(RequestStack $requestStack, string $emschEnv, array $environments)
    {
        $this->requestStack = $requestStack;
        $this->emschEnv = $emschEnv;

        foreach ($environments as $name => $config) {
            $this->environments[] = new Environment($name, $config);
        }
    }

    public function addEnvironment(Environment $environment): void
    {
        $this->environments[] = $environment;
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

        return $current ? $current->get('_backend') : null;
    }

    /**
     * Important for twig loader on kernel terminate we don't have a current request.
     * So this function remembers it's environment and can still return it.
     */
    public function getEnvironmentSuffix(): ?string
    {
        static $suffix = null;

        if (null !== $suffix) {
            return $suffix;
        }

        $current = $this->requestStack->getCurrentRequest();
        if (null !== $current) {
            $suffix = $current->get('_environment', null);
        } elseif ('cli' === PHP_SAPI) {
            $suffix = $this->emschEnv;
        }

        return $suffix;
    }

    public function getLocale(): string
    {
        $current = $this->requestStack->getCurrentRequest();
        if (null === $current) {
            throw new \RuntimeException('Unexpected null request');
        }

        return $current->getLocale();
    }

    public function getCurrentEnvironment(): Environment
    {
        $current = $this->requestStack->getCurrentRequest();
        if (null === $current) {
            throw new EnvironmentNotFoundException();
        }

        foreach ($this->environments as $environment) {
            if (null !== $current && $environment->matchRequest($current)) {
                return $environment;
            }
        }

        throw new EnvironmentNotFoundException();
    }

    public function getIndexSuffix(): string
    {
        try {
            return $this->getCurrentEnvironment()->getIndexSuffix();
        } catch (EnvironmentNotFoundException $e) {
        }
        $suffix = $this->getEnvironmentSuffix();
        if (null === $suffix) {
            throw new EnvironmentNotFoundException();
        }

        return $suffix;
    }
}
