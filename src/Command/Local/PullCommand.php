<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\PullHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PullCommand extends AbstractLocalCommand
{
    private PullHelper $pullHelper;

    public function __construct(EnvironmentHelper $environmentHelper, PullHelper $loginHelper)
    {
        parent::__construct($environmentHelper);
        $this->pullHelper = $loginHelper;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->pullHelper->setLogger($this->logger);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - pull');
        $this->io->section(\sprintf('Pulling for environment %s', $this->environment->getName()));

        $this->pullHelper->pull($this->environment->getLocal());

        return 1;
    }
}
