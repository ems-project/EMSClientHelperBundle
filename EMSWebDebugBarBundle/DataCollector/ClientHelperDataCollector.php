<?php

namespace EMS\ClientHelperBundle\EMSWebDebugBarBundle\DataCollector;

use EMS\ClientHelperBundle\EMSWebDebugBarBundle\Logger\ClientHelperLogger;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Class ClienttRequestDataCollector
 *
 * Collect all methods calls that are done in the service ClientRequestService
 */
class ClientHelperDataCollector extends DataCollector
{
    /**
     * @var ClientHelperLogger
     */
    protected $logger;
    
    public function __construct(ClientHelperLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, Exception $exception = null)
    {
        $this->data = $this->logger;
    }
    
    /**
     * @return int
     */
    public function getNumberOfElasticSearchQueries()
    {
        return $this->data->getNumberOfElasticSearchQueries();
    }
    
    /**
     * @return array
     */
    public function getElasticSearchLogs()
    {
        return $this->data->getElasticSearchLogs();
    }
    
    /**
     * @return array
     */
    public function getChronologicalLogs()
    {
        return $this->data->getChronologicalLogs();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emsch.data_collector';
    }
    
    /**
     * {@inheritdoc}
     */
    public function reset()
    {
    	//TODO
    }
}