<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;

final class PushHelper
{
    private LocalHelper $localHelper;
    private LoggerInterface $logger;

    public function __construct(LocalHelper $localHelper, LoggerInterface $logger)
    {
        $this->localHelper = $localHelper;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function push(Environment $environment): void
    {
        $localEnvironment = $this->localHelper->local($environment);
        $localEnvironment->setLogger($this->logger);
    }
}
