<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\LocalEnvironment;
use EMS\ClientHelperBundle\Helper\Local\LocalHelper;
use EMS\CommonBundle\Command\CommandInterface;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractLocalCommand extends Command implements CommandInterface
{
    protected CoreApiInterface $coreApi;
    protected Environment $environment;
    protected EnvironmentHelper $environmentHelper;
    protected LocalHelper $localHelper;
    protected LocalEnvironment $localEnvironment;
    protected LoggerInterface $logger;
    protected SymfonyStyle $io;

    private const OPTION_EMSCH_ENV = 'emsch_env';

    public function __construct(EnvironmentHelper $environmentHelper, LocalHelper $localHelper)
    {
        parent::__construct();
        $this->environmentHelper = $environmentHelper;
        $this->localHelper = $localHelper;
    }

    protected function configure(): void
    {
        $this->addOption(self::OPTION_EMSCH_ENV, null, InputArgument::OPTIONAL, 'emsch env name', null);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->logger = new ConsoleLogger($output);

        if (null === $input->getOption(self::OPTION_EMSCH_ENV)) {
            $input->setOption(self::OPTION_EMSCH_ENV, $this->environmentHelper->getEmschEnv());
        }

        $environmentName = \strval($input->getOption(self::OPTION_EMSCH_ENV));
        $environment = $this->environmentHelper->getEnvironment($environmentName);

        if (null === $environment) {
            throw new \RuntimeException(\sprintf('Environment with the name "%s" not found!', $environmentName));
        }

        $this->environment = $environment;
        $this->localEnvironment = $environment->getLocal();
        $this->localHelper->setLogger($this->logger);
        $this->coreApi = $this->localHelper->api($this->environment);
    }

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
