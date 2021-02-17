<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use Psr\Log\LoggerInterface;

final class StatusHelper
{
    private Builders $builders;
    private LoggerInterface $logger;

    public function __construct(Builders $builders, LoggerInterface $logger)
    {
        $this->builders = $builders;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
