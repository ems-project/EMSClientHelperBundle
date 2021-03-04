<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\Status\Status;
use EMS\ClientHelperBundle\Helper\Local\StatusHelper;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class StatusCommand extends AbstractLocalCommand
{
    private StatusHelper $statusHelper;

    public function __construct(EnvironmentHelper $environmentHelper, StatusHelper $loginHelper)
    {
        parent::__construct($environmentHelper);
        $this->statusHelper = $loginHelper;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->statusHelper->setLogger($this->logger);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - status');
        $this->io->section(\sprintf('Status for environment %s', $this->environment->getName()));

        $statusRouting = $this->statusHelper->routing($this->environment);
        $statusTemplating = $this->statusHelper->templating($this->environment);
        $statusTranslations = $this->statusHelper->translation($this->environment);

        $rows = [];
        $this->addStatusRow($statusRouting, $rows);
        $this->addStatusRow($statusTemplating, $rows);
        $this->addStatusRow($statusTranslations, $rows);

        $table = new Table($output);
        $table->setHeaders(['', 'Added', 'Updated', 'Deleted'])->setRows($rows)->render();

        if ($output->isVerbose()) {
            $this->printStatus($output, $statusTranslations);
            $this->printStatus($output, $statusTemplating);
            $this->printStatus($output, $statusRouting);
        }

        return 1;
    }

    private function printStatus(OutputInterface $output, Status $status): void
    {
        $this->io->newLine();

        $rows = [];

        foreach ($status->itemsAdded() as $item) { $rows[] = ['<fg=green>Added</>', $item->getKey()]; }
        foreach ($status->itemsUpdated() as $item) { $rows[] = ['<fg=blue>Updated</>', $item->getKey()]; }
        foreach ($status->itemsDeleted() as $item) { $rows[] = ['<fg=red>Deleted</>', $item->getKey()]; }

        $table = new Table($output);
        $table
            ->setHeaders([new TableCell($status->getName(), ['colspan' => 2])])
            ->setRows($rows)->render();

    }

    private function addStatusRow(Status $status, array &$rows)
    {
        $rows[] = [
            $status->getName(),
            sprintf('<fg=green>%d</>', $status->itemsAdded()->count()),
            sprintf('<fg=blue>%d</>', $status->itemsUpdated()->count()),
            sprintf('<fg=red>%d</>', $status->itemsDeleted()->count()),
        ];
    }
}
