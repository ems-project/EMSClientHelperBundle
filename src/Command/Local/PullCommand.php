<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\PullHelper;
use EMS\CommonBundle\Command\CommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class PullCommand extends Command implements CommandInterface
{
    private PullHelper $pullHelper;
    private EnvironmentHelper $environmentHelper;
    private SymfonyStyle $style;

    protected static $defaultName = 'emsch:local:pull';

    private const OPTION_EMSCH_ENV = 'emsch_env';

    public function __construct(PullHelper $pullService, EnvironmentHelper $environmentHelper)
    {
        parent::__construct();
        $this->pullHelper = $pullService;
        $this->environmentHelper = $environmentHelper;
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_EMSCH_ENV, null, InputArgument::OPTIONAL, 'emsch env name', null);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);

        if (null === $input->getOption(self::OPTION_EMSCH_ENV)) {
            $input->setOption(self::OPTION_EMSCH_ENV, $this->environmentHelper->getEmschEnv());
        }

        $this->pullHelper->setLogger(new ConsoleLogger($output));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->style->title('Local development - pull');

        $environmentName = \strval($input->getOption(self::OPTION_EMSCH_ENV));

        if (null === $environment = $this->environmentHelper->getEnvironment($environmentName)) {
            throw new \RuntimeException(\sprintf('Environment with the name "%s" not found!', $environmentName));
        }

        $this->style->section(\sprintf('Pulling for environment %s (%s)', $environment->getName(), $environment->getAlias()));
        $this->pullHelper->pull($environment);

        return 1;
    }
}
