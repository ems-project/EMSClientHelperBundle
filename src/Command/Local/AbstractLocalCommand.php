<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\CommonBundle\Command\CommandInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractLocalCommand extends Command implements CommandInterface
{
    protected Environment $environment;
    protected EnvironmentHelper $environmentHelper;
    protected LoggerInterface $logger;
    protected SymfonyStyle $io;

    private const OPTION_EMSCH_ENV = 'emsch_env';

    public function __construct(EnvironmentHelper $environmentHelper)
    {
        parent::__construct();
        $this->environmentHelper = $environmentHelper;
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_EMSCH_ENV, null, InputArgument::OPTIONAL, 'emsch env name', null);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->logger = new ConsoleLogger($output);

        if (null === $input->getOption(self::OPTION_EMSCH_ENV)) {
            $input->setOption(self::OPTION_EMSCH_ENV, $this->environmentHelper->getEmschEnv());
        }

        $environmentName = \strval($input->getOption(self::OPTION_EMSCH_ENV));
        $environment = $this->environmentHelper->getEnvironment($environmentName);

        if (null === $environment) {
            throw new \RuntimeException(\sprintf('Environment with the name "%s" not found!', $environmentName));
        }

        $this->environment = $environment;
    }
}
