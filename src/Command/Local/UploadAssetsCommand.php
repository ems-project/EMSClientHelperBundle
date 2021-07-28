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
            ->addArgument(self::ARG_BASE_URL, InputArgument::OPTIONAL, 'Base url where the assets are located', null)
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
        $hash = $this->coreApi->hashFile($assetsArchive);
        $filesize = \filesize($assetsArchive);
        if (!\is_int($filesize)) {
            throw new \RuntimeException('Unexpected file size type');
        }

        $fromByte = $this->coreApi->initUpload($hash, $filesize, 'bundle.zip', 'application/zip');
        if ($fromByte < 0) {
            throw new \RuntimeException(\sprintf('Unexpected negative offset: %d', $fromByte));
        }
        if ($fromByte > $filesize) {
            throw new \RuntimeException(\sprintf('Unexpected bigger offset than the filesize: %d > %d', $fromByte, $filesize));
        }

        $handle = \fopen($assetsArchive, 'r');
        if (false === $handle) {
            throw new \RuntimeException(\sprintf('Unexpected error while open the archive %s', $assetsArchive));
        }
        if ($fromByte > 0) {
            if (0 !== \fseek($handle, $fromByte)) {
                throw new \RuntimeException(\sprintf('Unexpected error while seeking the file pointer at position %s', $fromByte));
            }
        }

        if ($fromByte === $filesize) {
            $this->io->comment(\sprintf('The assets %s were already uploaded', $hash));

            return 1;
        }

        $this->io->progressStart($filesize);
        $uploaded = $fromByte;
        while (!\feof($handle)) {
            $chunk = \fread($handle, 819200);
            if (!\is_string($chunk)) {
                throw new \RuntimeException('Unexpected chunk type');
            }
            $uploaded = $this->coreApi->addChunk($hash, $chunk);
            $this->io->progressAdvance(\strlen($chunk));
        }
        \fclose($handle);
        $this->io->progressFinish();

        if ($uploaded !== $filesize) {
            $this->io->warning(\sprintf('Sizes mismatched %d vs. %d for assets %s', $uploaded, $filesize, $hash));

            return 1;
        }

        $this->io->success(\sprintf('Assets %s have been uploaded', $hash));

        return 1;
    }
}
