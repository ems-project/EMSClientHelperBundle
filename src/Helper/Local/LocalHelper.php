<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;

final class LocalHelper
{
    private LoggerInterface $logger;
    private string $path;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->path = $projectDir.DIRECTORY_SEPARATOR.'local';
    }

    public function local(Environment $environment): LocalEnvironment
    {
        return new LocalEnvironment($environment, $this->logger, $this->path);
    }
}
