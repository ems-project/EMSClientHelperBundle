<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use Psr\Log\LoggerInterface;

final class PushHelper
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function push(LocalEnvironment $localEnvironment): void
    {
        $localEnvironment->setLogger($this->logger);
    }
}
