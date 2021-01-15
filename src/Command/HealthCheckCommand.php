<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Command;

use EMS\ClientHelperBundle\Exception\ClusterHealthNotGreenException;
use EMS\ClientHelperBundle\Exception\ClusterHealthRedException;
use EMS\ClientHelperBundle\Exception\IndexNotFoundException;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\CommonBundle\Service\ElasticaService;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class HealthCheckCommand extends Command
{
    /** @var ClientRequest[] */
    private iterable $clientRequests;
    private EnvironmentHelper $environmentHelper;
    private ?StorageManager $storageManager;
    private ElasticaService $elasticaService;

    /**
     * @param ClientRequest[] $clientRequests
     */
    public function __construct(
        EnvironmentHelper $environmentHelper,
        ElasticaService $elasticaService,
        iterable $clientRequests = null,
        StorageManager $storageManager = null
    ) {
        parent::__construct();
        $this->environmentHelper = $environmentHelper;
        $this->elasticaService = $elasticaService;
        $this->clientRequests = $clientRequests ?? [];
        $this->storageManager = $storageManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('emsch:health-check')
            ->setDescription('Performs system health check.')
            ->setHelp('Verify that the assets folder exists and is not empty. Verify that the Elasticsearch cluster is at least yellow and that the configured indexes exist.')
            ->addOption('green', 'g', InputOption::VALUE_NONE, 'Require a green Elasticsearch cluster health.', null)
            ->addOption('skip-storage', 's', InputOption::VALUE_NONE, 'Skip the storage health check.', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Performing Health Check');

        $this->checkElasticSearch($io, (true === $input->getOption('green')));
        $this->checkIndexes($io);
        $this->checkStorage($io, (true === $input->getOption('skip-storage')));

        $io->success('Health check finished.');

        return 1;
    }

    private function checkElasticSearch(SymfonyStyle $io, bool $green): void
    {
        $io->section('Elasticsearch');
        $status = $this->elasticaService->getHealthStatus();

        if ('red' === $status) {
            $io->error('Cluster health is RED');
            throw new ClusterHealthRedException();
        }

        if ($green && 'green' !== $status) {
            $io->error('Cluster health is NOT GREEN');
            throw new ClusterHealthNotGreenException();
        }

        $io->success('Elasticsearch is working.');
    }

    private function checkIndexes(SymfonyStyle $io): void
    {
        $io->section('Indexes');
        $countAliases = 0;
        $countIndices = 0;
        foreach ($this->environmentHelper->getEnvironments() as $environment) {
            ++$countAliases;
            try {
                $countIndices += \count($this->elasticaService->getIndicesFromAlias($environment->getAlias()));
            } catch (\Throwable $e) {
                $io->error(\sprintf('Alias %s not found with error: %s', $environment->getAlias(), $e->getMessage()));
                throw new IndexNotFoundException();
            }
        }

        $io->success(\sprintf('%d indices have been found in %d aliases.', $countIndices, $countAliases));
    }

    private function checkStorage(SymfonyStyle $io, bool $skip): void
    {
        $io->section('Storage');

        if ($skip) {
            $io->note('Skipping Storage Health Check.');

            return;
        }

        if (null === $this->storageManager) {
            $io->warning('Skipping assets because health check has no access to a storageManager, enable storage ?');

            return;
        }

        $adapters = [];

        foreach ($this->storageManager->getHealthStatuses() as $name => $status) {
            $adapters[] = $name.' -> '.($status ? 'green' : 'red');
        }

        $io->listing($adapters);
    }
}
