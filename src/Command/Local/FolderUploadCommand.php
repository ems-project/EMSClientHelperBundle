<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\CommonBundle\Command\CommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

final class FolderUploadCommand extends AbstractUploadCommand implements CommandInterface
{
    private const ARG_FOLDER = 'folder';

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument(self::ARG_FOLDER, InputArgument::REQUIRED, 'Folder where are located the assets to upload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Local development - Upload all assets located in a folder');
        $folder = $input->getArgument(self::ARG_FOLDER);

        if (!$this->coreApi->isAuthenticated()) {
            $this->io->error(\sprintf('Not authenticated for %s, run emsch:local:login', $this->coreApi->getBaseUrl()));

            return self::EXECUTE_ERROR;
        }

        $finder = new Finder();

        $finder->files()->in($folder);

        if (!$finder->hasResults()) {
            $this->io->error('No file found');

            return self::EXECUTE_ERROR;
        }

        $this->io->comment(\sprintf('%d files located', $finder->count()));
        $uploadedCounter = 0;
        $counter = 0;
        foreach ($finder as $file) {
            ++$counter;
            $realPath = $file->getRealPath();
            if (!\is_string($realPath)) {
                $this->io->comment(\sprintf('File %s not found', $file->getFilename()));
                continue;
            }
            $this->io->comment(\sprintf('File %s %d/%d :', $file->getFilename(), $counter, $finder->count()));
            if (null !== $this->uploadFile($realPath)) {
                ++$uploadedCounter;
            }
        }
        $this->io->success(\sprintf('%d (on %d) assets have been uploaded', $uploadedCounter, $finder->count()));

        return self::EXECUTE_SUCCESS;
    }
}
