<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Admin;

use EMS\ClientHelperBundle\Command\Local\AbstractLocalCommand;
use EMS\ClientHelperBundle\Helper\Api\AdminConfigService;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\LocalHelper;
use EMS\CommonBundle\Common\CoreApi\Endpoint\Admin\ContentType;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GetCommand extends AbstractLocalCommand
{
    public const CONFIG_TYPE = 'config-type';
    public const EXPORT = 'export';
    private AdminConfigService $adminConfigService;
    private string $folder;
    private string $configType;
    private ConfigInterface $config;
    private bool $export;

    public function __construct(EnvironmentHelper $environmentHelper, LocalHelper $localHelper, string $projectFolder)
    {
        $this->folder = $projectFolder.DIRECTORY_SEPARATOR.'admin';
        parent::__construct($environmentHelper, $localHelper);
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument(self::CONFIG_TYPE, InputArgument::REQUIRED, \sprintf('Type of config to get, possible values: %s', \implode(', ', [
            ContentType::CONTENT_TYPE,
        ])));
        $this->addOption(self::EXPORT, null, InputOption::VALUE_NONE, 'Export config in JSON files');
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->configType = $this->getArgumentString(self::CONFIG_TYPE);
        $this->export = $this->getOptionBool(self::EXPORT);
        $this->config = $this->coreApi->admin()->getConfig($this->configType);
        $this->adminConfigService = new AdminConfigService($this->config, $this->folder);
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

        if ($this->export) {
            $this->adminConfigService->update();
        }

        $rows = [];
        foreach ($this->config->index() as $key => $name) {
            $rows[] = [$key, $name];
        }

        $this->io->table(['#', 'Name'], $rows);

        return self::EXECUTE_SUCCESS;
    }
}
