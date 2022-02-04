<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Admin;

use EMS\ClientHelperBundle\Command\Local\AbstractLocalCommand;
use EMS\ClientHelperBundle\Helper\Api\AdminConfigService;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\LocalHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GetCommand extends AbstractLocalCommand
{
    private AdminConfigService $adminConfigService;
    private string $folder;

    public function __construct(EnvironmentHelper $environmentHelper, LocalHelper $localHelper, string $projectFolder)
    {
        $this->folder = $projectFolder.DIRECTORY_SEPARATOR.'admin';
        parent::__construct($environmentHelper, $localHelper);
    }

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

        foreach ($this->coreApi->admin()->getConfigs() as $config) {
            $this->adminConfigService = new AdminConfigService($config, $this->folder);
            $this->adminConfigService->update();
        }

        return self::EXECUTE_SUCCESS;
    }
}
