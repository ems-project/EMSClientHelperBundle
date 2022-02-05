<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Admin;

use EMS\ClientHelperBundle\Command\Local\AbstractLocalCommand;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\LocalHelper;
use EMS\CommonBundle\Common\CoreApi\Endpoint\Admin\ContentType;
use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateCommand extends AbstractLocalCommand
{
    public const CONFIG_TYPE = 'config-type';
    public const ENTITY_NAME = 'entity-name';
    public const JSON_PATH = 'json-path';
    private string $configType;
    private ConfigInterface $config;
    private string $entityName;
    private string $jsonPath;

    public function __construct(EnvironmentHelper $environmentHelper, LocalHelper $localHelper)
    {
        parent::__construct($environmentHelper, $localHelper);
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument(self::CONFIG_TYPE, InputArgument::REQUIRED, \sprintf('Type of config to get, possible values: %s', \implode(', ', [
            ContentType::CONTENT_TYPE,
        ])));
        $this->addArgument(self::ENTITY_NAME, InputArgument::REQUIRED, 'Entity\'s name to update');
        $this->addArgument(self::JSON_PATH, InputArgument::REQUIRED, 'Path to the JSON file');
    }

    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->configType = $this->getArgumentString(self::CONFIG_TYPE);
        $this->entityName = $this->getArgumentString(self::ENTITY_NAME);
        $this->jsonPath = $this->getArgumentString(self::JSON_PATH);
        $this->config = $this->coreApi->admin()->getConfig($this->configType);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Admin - update');
        $this->io->section(\sprintf('Updating configurations to %s', $this->environment->getBackendUrl()));
        if (!$this->healthCheck()) {
            return self::EXECUTE_ERROR;
        }
        if (!$this->coreApi->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run emsch:local:login', $this->coreApi->getBaseUrl()));

            return self::EXECUTE_ERROR;
        }
        $fileContent = \file_get_contents($this->jsonPath);
        if (!\is_string($fileContent)) {
            throw new \RuntimeException('Unexpected non string file content');
        }
        $this->config->update($this->entityName, Json::decode($fileContent));

        return self::EXECUTE_SUCCESS;
    }
}
