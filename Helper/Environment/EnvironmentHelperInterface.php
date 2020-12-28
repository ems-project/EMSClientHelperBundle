<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Environment;

interface EnvironmentHelperInterface
{
    /**
     * @return Environment[]
     */
    public function getEnvironments(): array;

    public function getBackend(): ?string;

    public function getEnvironment(): ?string;

    public function getLocale(): string;
}
