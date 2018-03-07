<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Command;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\EventListener\RequestListener;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\AssetsFolderEmptyException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\AssetsFolderNotFoundException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\ClusterHealthNotGreenException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\ClusterHealthRedException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\IndexNotFoundException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\NoClientsFoundException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Storage\StorageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writelnInfo($output, 'Performing Health Check: ');
        
        $this->checkElasticSearch($input, $output);
        $this->checkIndexes($input, $output);
        $this->checkAssets($input, $output);
        
        $this->writelnInfo($output, 'Health Check OK.');
    }
    
    private function checkElasticSearch(InputInterface $input, OutputInterface $output)
    {
        $this->writeInfo($output, 'Elasticsearch: ');
        if (empty($this->clients)){
            $this->writelnError($output, 'No clients found');
            throw new NoClientsFoundException();
        }
        
        foreach ($this->clients as $client) {
            if ('red' === $client->cluster()->health()){
                $this->writelnError($output, 'Cluster health is RED');
                throw new ClusterHealthRedException();
            }
            
            if ($input->getOption('green') && 'green' !== $client->cluster()->health()){
                $this->writelnError($output, 'Cluster health is NOT GREEN');
                throw new ClusterHealthNotGreenException();
            }
        }
        $this->writelnInfo($output, 'OK');
    }
    
    private function checkIndexes(InputInterface $input, OutputInterface $output)
    {
        $this->writeInfo($output, 'Indexes: ');
        
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
                $this->writelnError($output, 'Index '.$index.' not found');
                throw new IndexNotFoundException();
            }
        }
        
        $this->writelnInfo($output, 'OK');
    }
    
    private function checkAssets(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('skip-assets'))
        {
            $this->writelnInfo($output, 'Skipping Asset Health Check.');
            return;
        }
        
        $this->writeInfo($output, 'Assets: ');
        
        if(null === $this->storageService){
            $this->writelnWarning($output, 'Skipping assets because health check has no access to a storageService, is your service tagged with emsch.storage_service ?');
            return;
        }
        
        $this->writeInfo($output, $this->storageService->getBasePath().': ');
        
        if(!$this->storageService->storageExists()){
            $this->writelnError($output, 'Assets folder not found');
            throw new AssetsFolderNotFoundException();
        }
        
        if($this->storageService->storageIsEmpty()){
            $this->writelnError($output, 'Assets folder is empty');
            throw new AssetsFolderEmptyException();
        }
        
        $this->writelnInfo($output, 'OK');
    }
    
    
    private function writelnInfo(OutputInterface $output, $text)
    {
        $output->writeln('<info>'.$text.'</info>');
    }
    private function writeInfo(OutputInterface $output, $text)
    {
        $output->write('<info>'.$text.'</info>');
    }
    private function writelnError(OutputInterface $output, $text)
    {
        $output->writeln('<error>'.$text.'</error>');
    }
    private function writelnWarning(OutputInterface $output, $text)
    {
        $output->writeln('<comment>'.$text.'</comment>');
    }
}
