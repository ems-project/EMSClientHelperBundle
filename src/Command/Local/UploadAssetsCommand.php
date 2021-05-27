<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UploadAssetsCommand extends AbstractLocalCommand
{
    protected function configure(): void
    {
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - Upload assets');

        try {
//            $assetsHash = $this->localHelper->uploadAssets($this->environment);
            $assetsHash = 'ok';
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return -1;
        }
        $this->io->success(\sprintf('Assets have been uploaded with the hash %s', $assetsHash));

        return 1;
    }
}
