<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Admin;

use EMS\ClientHelperBundle\Command\Local\AbstractLocalCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GetCommand extends AbstractLocalCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Admin - get');
        $this->io->section(\sprintf('Getting configurations from %s', $this->environment->getBackendUrl()));
        if (!$this->healthCheck()) {
            return self::EXECUTE_ERROR;
        }
        if (!$this->coreApi->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run emsch:local:login', $this->coreApi->getBaseUrl()));

            return self::EXECUTE_ERROR;
        }

        return self::EXECUTE_SUCCESS;
    }
}
