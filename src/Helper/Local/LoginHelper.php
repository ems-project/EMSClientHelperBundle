<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiFactoryInterface;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\User\ProfileInterface;
use EMS\CommonBundle\Contracts\CoreApi\Exception\NotAuthenticatedExceptionInterface;
use Psr\Log\LoggerInterface;

final class LoginHelper
{
    private CoreApiFactoryInterface $coreApiFactory;
    private LoggerInterface $logger;

    public function __construct(CoreApiFactoryInterface $coreApiFactory, LoggerInterface $logger)
    {
        $this->coreApiFactory = $coreApiFactory;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function login(Environment $environment, string $username, string $password): ?ProfileInterface
    {
        $coreApi = $this->createCoreApi($environment);

        try {
            $coreApi->authenticate($username, $password);
        } catch (NotAuthenticatedExceptionInterface $e) {
            $this->logger->error('Invalid credentials!');

            return null;
        }

        return $coreApi->user()->getProfileAuthenticated();
    }

    private function createCoreApi(Environment $environment): CoreApiInterface
    {
        if (null === $backendUrl = $environment->getBackendUrl()) {
            throw new \RuntimeException(\sprintf('Please define "backend" option for environment %s', $environment->getName()));
        }

        $this->logger->notice(\sprintf('Using %s', $backendUrl));

        $coreApi = $this->coreApiFactory->create($backendUrl);
        $coreApi->setLogger($this->logger);

        return $coreApi;
    }
}
