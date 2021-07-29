<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

abstract class AbstractUploadCommand extends AbstractLocalCommand
{
    protected function uploadFile(string $filename): ?string
    {
        $hash = $this->coreApi->hashFile($filename);
        $filesize = \filesize($filename);
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

        $handle = \fopen($filename, 'r');
        if (false === $handle) {
            throw new \RuntimeException(\sprintf('Unexpected error while open the archive %s', $filename));
        }
        if ($fromByte > 0) {
            if (0 !== \fseek($handle, $fromByte)) {
                throw new \RuntimeException(\sprintf('Unexpected error while seeking the file pointer at position %s', $fromByte));
            }
        }

        if ($fromByte === $filesize) {
            $this->io->comment(\sprintf('The assets %s were already uploaded', $hash));

            return $hash;
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

            return null;
        }

        return $hash;
    }
}
