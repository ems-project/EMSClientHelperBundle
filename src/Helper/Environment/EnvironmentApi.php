<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Environment;

use EMS\CommonBundle\Contracts\CoreApi\CoreApiFactoryInterface;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;

final class EnvironmentApi
{
    private CoreApiFactoryInterface $coreApiFactory;

    public function __construct(CoreApiFactoryInterface $coreApiFactory)
    {
        $this->coreApiFactory = $coreApiFactory;
    }

    public function api(Environment $environment): CoreApiInterface
    {
        $backendUrl = $this->getBackendUrl($environment);

        return $this->coreApiFactory->create($backendUrl);
    }

    public function login(Environment $environment, string $username, string $password): CoreApiInterface
    {
        $backendUrl = $this->getBackendUrl($environment);
        $coreApi = $this->coreApiFactory->create($backendUrl);
        $coreApi->authenticate($username, $password);

        return $coreApi;
    }

    private function getBackendUrl(Environment $environment): string
    {
        if (null === $backendUrl = $environment->getBackendUrl()) {
            throw new \RuntimeException(\sprintf('Please define "backend" option for environment %s', $environment->getName()));
        }

        return $backendUrl;
    }
}
