<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\PushHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PushCommand extends AbstractLocalCommand
{
    private PushHelper $pushHelper;

    public function __construct(EnvironmentHelper $environmentHelper, PushHelper $loginHelper)
    {
        parent::__construct($environmentHelper);
        $this->pushHelper = $loginHelper;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->pushHelper->setLogger($this->logger);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - push');
        $this->io->section(\sprintf('Pulling for environment %s', $this->environment->getName()));

        $this->pushHelper->push($this->environment->getLocal());

        return 1;
    }
}
