<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PullCommand extends AbstractLocalCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - pull');
        $this->io->section(\sprintf('Pulling for environment %s', $this->environment->getName()));

        $this->localHelper->build($this->environment);

        if ($this->environment->isLocalPulled()) {
            $this->io->success(\sprintf('Pulled successfully into %s', $this->localEnvironment->getDirectory()));
        }

        $localEnvironment = $this->environment->getLocal();

        $list = [];
        foreach ($localEnvironment->getTranslations() as $translationFile) {
            $list[] = [\sprintf('translations %s', \strtoupper($translationFile->locale)) => \count($translationFile)];
        }
        $list[] = ['templates' => $localEnvironment->getTemplates()->count()];
        $list[] = ['routes' => $localEnvironment->getRouting()->count()];

        $this->io->definitionList(...$list);

        return 1;
    }
}
