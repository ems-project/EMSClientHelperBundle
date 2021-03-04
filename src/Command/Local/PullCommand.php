<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\PullHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PullCommand extends AbstractLocalCommand
{
    private PullHelper $pullHelper;

    public function __construct(EnvironmentHelper $environmentHelper, PullHelper $pullHelper)
    {
        parent::__construct($environmentHelper);
        $this->pullHelper = $pullHelper;
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

        $this->pullHelper->pull($this->environment);

        if ($this->environment->isLocalPulled()) {
            $this->io->success(sprintf('Pulled successfully into %s', $this->environment->getLocal()->getDirectory()));
        }

        $localEnvironment = $this->environment->getLocal();

        $rows = [];
        foreach ($localEnvironment->getTranslations() as $translationFile) {
            $rows[] = [sprintf('translations %s', strtoupper($translationFile->locale)), \count($translationFile)];
        }
        $rows[] = new TableSeparator();
        $rows[] = ['templates', $localEnvironment->getTemplates()->count()];
        $rows[] = new TableSeparator();
        $rows[] = ['routes', $localEnvironment->getRouting()->count()];

        $table = new Table($output);
        $table->setRows($rows);
        $table->render();

        return 1;
    }
}
