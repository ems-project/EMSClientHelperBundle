<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UploadAssetsCommand extends AbstractLocalCommand
{
    private const ARG_BASE_URL = 'base_url';

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument(self::ARG_BASE_URL, InputArgument::OPTIONAL, 'Base url where the assets are located')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - Upload assets');
        $baseUrl = $input->getArgument(self::ARG_BASE_URL);
        if (!\is_string($baseUrl)) {
            $baseUrl = $this->environment->getAlias();
        }

        if (!$this->coreApi->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run emsch:local:login', $this->coreApi->getBaseUrl()));

            return -1;
        }

        try {
            $assetsArchive = $this->localHelper->makeAssetsArchives($baseUrl);
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return -1;
        }

        $hash = $this->uploadFile($assetsArchive);

        if (null === $hash) {
            return 1;
        }

        $this->io->success(\sprintf('Assets %s have been uploaded', $hash));

        return 0;
    }
}
