<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\StatusHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StatusCommand extends AbstractLocalCommand
{
    private StatusHelper $statusHelper;

    public function __construct(EnvironmentHelper $environmentHelper, StatusHelper $loginHelper)
    {
        parent::__construct($environmentHelper);
        $this->statusHelper = $loginHelper;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->statusHelper->setLogger($this->logger);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - status');

        return 1;
    }
}
