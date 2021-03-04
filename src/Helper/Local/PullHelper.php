<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;

final class PullHelper
{
    private Builders $builders;
    private LoggerInterface $logger;

    public function __construct(Builders $builders, LoggerInterface $logger)
    {
        $this->builders = $builders;
        $this->logger = $logger;
    }

    public function pull(Environment $environment): void
    {
        $directory = $environment->getLocal()->getDirectory();

        $this->builders->translation()->buildFiles($environment, $directory);
        $this->builders->templating()->buildFiles($environment, $directory);
        $this->builders->routing()->buildFiles($environment, $directory);

        $environment->getLocal()->loadFiles();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
