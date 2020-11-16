<?php

namespace EMS\ClientHelperBundle\Command;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Exception\ClusterHealthNotGreenException;
use EMS\ClientHelperBundle\Exception\ClusterHealthRedException;
use EMS\ClientHelperBundle\Exception\IndexNotFoundException;
use EMS\ClientHelperBundle\Exception\NoClientsFoundException;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class HealthCheckCommand extends Command
{
    /**
     * @var Client[]
     */
    private $clients = [];

    /**
     * @var ClientRequest[]
     */
    private $clientRequests;

    /**
     * @var EnvironmentHelper
     */
    private $environmentHelper;

    /**
     * @var StorageManager
     */
    private $storageManager;

    /**
     * @param EnvironmentHelper $environmentHelper
     * @param iterable          $clients
     * @param iterable          $clientRequests
     */
    public function __construct(EnvironmentHelper $environmentHelper, iterable $clients = null, iterable $clientRequests = null, StorageManager $storageManager = null)
    {
        $this->environmentHelper = $environmentHelper;
        $this->clients = $clients ?? [];
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
     * @param SymfonyStyle $io
     * @param bool         $green
     *
     * @throws NoClientsFoundException
     * @throws ClusterHealthRedException
     * @throws ClusterHealthNotGreenException
     */
    private function checkElasticSearch(SymfonyStyle $io, $green)
    {
        $io->section('Elasticsearch');
        if (empty($this->clients)) {
            $io->error('No clients found');
            throw new NoClientsFoundException();
        }

        foreach ($this->clients as $client) {
            if ('red' === $client->cluster()->health()['status']) {
                $io->error('Cluster health is RED');
                throw new ClusterHealthRedException();
            }

            if ($green && 'green' !== $client->cluster()->health()['status']) {
                $io->error('Cluster health is NOT GREEN');
                throw new ClusterHealthNotGreenException();
            }
        }
        $io->success('Elasticsearch is working.');
    }

    /**
     * @param SymfonyStyle $io
     * @throws IndexNotFoundException
     */
    private function checkIndexes(SymfonyStyle $io)
    {
        $io->section('Indexes');

        $prefixes = [];
        foreach ($this->clientRequests as $clientRequest) {
            $prefixes = array_merge($prefixes, $clientRequest->getPrefixes());
        }
        $postfixes = [];
        foreach ($this->environmentHelper->getEnvironments() as $environment) {
            $postfixes[] = $environment->getIndex();
        }
        $indexes = [];
        foreach ($prefixes as $preValue) {
            foreach ($postfixes as $postValue) {
                $indexes[] = $preValue . $postValue;
            }
        }

        $index = join(',', $indexes);

        foreach ($this->clients as $client) {
            if (!$client->indices()->exists(['index' => $index])) {
                $io->error('Index ' . $index . ' not found');
                throw new IndexNotFoundException();
            }
        }

        $io->success('Indexes are found.');
    }

    /**
     * @param SymfonyStyle $io
     * @param bool         $skip
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

        foreach ($this->storageManager->getAdapters() as $adapter) {
            $adapters[] = get_class($adapter) . ' -> ' . ($adapter->health() ? 'green' : 'red');
        }

        $io->listing($adapters);
    }
}
