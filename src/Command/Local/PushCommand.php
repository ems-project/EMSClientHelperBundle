<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Local\Status\Item;
use EMS\ClientHelperBundle\Helper\Local\Status\Status;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PushCommand extends AbstractLocalCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - push');
        $this->io->section(\sprintf('Pushing for environment %s', $this->environment->getName()));

        if (!$this->localHelper->isUpToDate($this->environment)) {
            $this->io->error('Not up to date, please commit/stash changes and run emsch:local:pull');

            return -1;
        }

        if (!$this->coreApi->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run emsch:local:login', $this->coreApi->getBaseUrl()));

            return -1;
        }

        foreach ($this->localHelper->statuses($this->environment) as $status) {
            $this->pushStatus($status);
        }

        \sleep(3); //otherwise we may get an incorrect up to date
        $this->localHelper->buildVersion($this->environment, true);

        return 1;
    }

    private function pushStatus(Status $status): void
    {
        $this->io->section($status->getName());

        foreach ($status->itemsAdded() as $item) {
            $data = $this->coreApi->data($item->getContentType());
            $draft = $data->create($item->getDataLocal());
            $ouuid = $data->finalize($draft->getRevisionId());
            $item->setId($ouuid);
            $pushes[] = ['Created' => $this->getUrl($item)];
        }

        foreach ($status->itemsUpdated() as $item) {
            $data = $this->coreApi->data($item->getContentType());
            $draft = $data->update($item->getId(), $item->getDataLocal());
            $data->finalize($draft->getRevisionId());
            $pushes[] = ['Updated' => $this->getUrl($item)];
        }

        foreach ($status->itemsDeleted() as $item) {
            $data = $this->coreApi->data($item->getContentType());
            $data->delete($item->getId());
            $pushes[] = ['Deleted' => $this->getUrl($item)];
        }
    }

    private function getUrl(Item $item): string
    {
        return \vsprintf('%s (%s/data/revisions/%s:%s)', [
            $item->getKey(),
            $this->coreApi->getBaseUrl(),
            $item->getContentType(),
            $item->getId(),
        ]);
    }
}
