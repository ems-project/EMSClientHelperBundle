<?php

namespace EMS\ClientHelperBundle\Command;

use EMS\ClientHelperBundle\Exception\ClusterHealthNotGreenException;
use EMS\ClientHelperBundle\Exception\ClusterHealthRedException;
use EMS\ClientHelperBundle\Exception\IndexNotFoundException;
use EMS\ClientHelperBundle\Exception\NoClientsFoundException;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\CommonBundle\Service\ElasticaService;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HealthCheckCommand extends Command
{
    /** @var ClientRequest[] */
    private $clientRequests;

    /** @var EnvironmentHelper */
    private $environmentHelper;

    /** @var StorageManager */
    private $storageManager;

    private ElasticaService $elasticaService;

    /**
     * @param iterable $clientRequests
     */
    public function __construct(EnvironmentHelper $environmentHelper, ElasticaService $elasticaService, iterable $clientRequests = null, StorageManager $storageManager = null)
    {
        $this->environmentHelper = $environmentHelper;
        $this->elasticaService = $elasticaService;
        $this->clientRequests = $clientRequests ?? [];
        $this->storageManager = $storageManager;

        parent::__construct();
    }

    protected function configure()
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

        $this->checkElasticSearch($io, $input->getOption('green'));
        $this->checkIndexes($io);
        $this->checkStorage($io, $input->getOption('skip-storage'));

        $io->success('Health check finished.');

        return 1;
    }

    /**
     * @param bool $green
     *
     * @throws NoClientsFoundException
     * @throws ClusterHealthRedException
     * @throws ClusterHealthNotGreenException
     */
    private function checkElasticSearch(SymfonyStyle $io, $green)
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

    /**
     * @throws IndexNotFoundException
     */
    private function checkIndexes(SymfonyStyle $io)
    {
        $io->section('Indexes');
        $countAliases = 0;
        $countIndices = 0;
        foreach ($this->environmentHelper->getEnvironments() as $environment) {
            ++$countAliases;
//            try {
            $countIndices += \count($this->elasticaService->getIndicesFromAlias($environment->getName()));
//            } catch (\Throwable $e) {
//                $io->error(\sprintf('Alias %s not found with error: %s', $environment->getName(), $e->getMessage()));
//                throw new IndexNotFoundException();
//            }
        }

        $io->success(\sprintf('%d indices have been found in %d aliases.', $countIndices, $countAliases));
    }

    /**
     * @param bool $skip
     *
     * @return void
     */
    private function checkStorage(SymfonyStyle $io, $skip)
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
