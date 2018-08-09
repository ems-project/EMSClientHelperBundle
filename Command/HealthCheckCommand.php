<?php

namespace EMS\ClientHelperBundle\Command;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\Helper\Request\ClientRequest;
use EMS\ClientHelperBundle\EventListener\RequestListener;
use EMS\ClientHelperBundle\Exception\AssetsFolderEmptyException;
use EMS\ClientHelperBundle\Exception\AssetsFolderNotFoundException;
use EMS\ClientHelperBundle\Exception\ClusterHealthNotGreenException;
use EMS\ClientHelperBundle\Exception\ClusterHealthRedException;
use EMS\ClientHelperBundle\Exception\IndexNotFoundException;
use EMS\ClientHelperBundle\Exception\NoClientsFoundException;
use EMS\ClientHelperBundle\Storage\StorageService;
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
     * @var StorageService
     */
    private $storageService;
    
    /**
     * @var RequestListener
     */
    private $requestListener;
    
    /**
     * @var ClientRequest[]
     */
    private $clientRequests;
    
    /**
     * @param array $clients
     */
    public function setClients(array $clients)
    {
        $this->clients = $clients;
    }
    
    /**
     * @param StorageService $storageService
     */
    public function setStorageService(StorageService $storageService)
    {
        $this->storageService = $storageService;
    }
    
    /**
     * @param array $clientRequests
     */
    public function setClientRequests(array $clientRequests)
    {
        $this->clientRequests = $clientRequests;
    }
    
    /**
     * @param RequestListener $requestListener
     */
    public function __construct(RequestListener $requestListener)
    {
        $this->requestListener = $requestListener;
         
        parent::__construct();
    }
    
    protected function configure()
    {
        $this
            ->setName('emsch:health-check')
            ->setDescription('Performs system health check.')
            ->setHelp('Verify that the assets folder exists and is not empty. Verify that the Elasticsearch cluster is at least yellow and that the configured indexes exist.')
            ->addOption('green', 'g', InputOption::VALUE_NONE, 'Require a green Elasticsearch cluster health.', null)
            ->addOption('skip-assets', 's', InputOption::VALUE_NONE, 'Skip the assets folder health check.', null);
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Performing Health Check');
        
        $this->checkElasticSearch($io, $input->getOption('green'));
        $this->checkIndexes($io);
        $this->checkAssets($io, $input->getOption('skip-assets'));
        
        $io->success('Health check finished.');
    }
    
    /**
     * @param SymfonyStyle $io
     * @param bool $green
     * @throws NoClientsFoundException
     * @throws ClusterHealthRedException
     * @throws ClusterHealthNotGreenException
     */
    private function checkElasticSearch(SymfonyStyle $io, $green)
    {
        $io->section('Elasticsearch');
        if (empty($this->clients)){
            $io->error('No clients found');
            throw new NoClientsFoundException();
        }
        
        foreach ($this->clients as $client) {
            if ('red' === $client->cluster()->health()){
                $io->error('Cluster health is RED');
                throw new ClusterHealthRedException();
            }
            
            if ($green && 'green' !== $client->cluster()->health()){
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
        foreach ($this->requestListener->getRequestEnvironments() as $environment) {
            $postfixes[] = $environment->getIndex();
        }
        $indexes = [];
        foreach($prefixes as $preValue){
            foreach($postfixes as $postValue){
                $indexes[] = $preValue.$postValue;
            }
        }
        
        $index = join(',', $indexes);
        
        foreach ($this->clients as $client) {
            if (!$client->indices()->exists(['index' => $index])){
                $io->error('Index '.$index.' not found');
                throw new IndexNotFoundException();
            }
        }
        
        $io->success('Indexes are found.');
    }
    
    /**
     * @param SymfonyStyle $io
     * @param bool $skip
     * @return void
     * @throws AssetsFolderNotFoundException
     * @throws AssetsFolderEmptyException
     */
    private function checkAssets(SymfonyStyle $io, $skip)
    {
        $io->section('Assets');
        
        if ($skip)
        {
            $io->note('Skipping Asset Health Check.');
            return;
        }
        
        if(null === $this->storageService){
            $io->warning('Skipping assets because health check has no access to a storageService, is your service tagged with emsch.storage_service ?');
            return;
        }
        
        $io->text($this->storageService->getBasePath());
        
        if(!$this->storageService->storageExists()){
            $io->error('Assets folder not found');
            throw new AssetsFolderNotFoundException();
        }
        
        if($this->storageService->storageIsEmpty()){
            $io->error('Assets folder is empty');
            throw new AssetsFolderEmptyException();
        }
        
        $io->success('Assets are found.');
    }
}
