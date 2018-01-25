<?php

namespace EMS\ClientHelperBundle\EMSWebDebugBarBundle\Logger;

use EMS\ClientHelperBundle\EMSWebDebugBarBundle\Entity\ElasticSearchLog;
use EMS\ClientHelperBundle\EMSWebDebugBarBundle\Entity\Log;
use Symfony\Component\Stopwatch\Stopwatch;

class ClientHelperLogger
{
    /**
     * @var Stopwatch $stopwatch
     */
    protected $stopwatch;
    
    /**
     * @var array
     */
    protected $logsByType = [];
    
    /**
     * @var array
     */
    protected $logsChronological = [];
    
    /**
     * @var int
     */
    protected $numberOfElasticSearchQueries = 0;

    /**
     * ClientHelperLogger constructor.
     * @param Stopwatch $stopwatch
     */
    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
        
        $this->logsByType[Log::ELASTICSEARCH_LOG] = [];
    }
    
    /**
     * @return int
     */
    public function getNumberOfElasticSearchQueries()
    {
        return $this->numberOfElasticSearchQueries;
    }
    
    /**
     * @return array
     */
    public function getElasticSearchLogs()
    {        
        return $this->logsByType[Log::ELASTICSEARCH_LOG];
    }
    
    /**
     * @return array
     */
    public function getChronologicalLogs()
    {
        return $this->logsChronological;
    }

    /**
     * @param ElasticsearchLog $log
     */
    public function logElasticsearch(ElasticSearchLog $log)
    {
        $this->log(Log::ELASTICSEARCH_LOG, $log);
        $this->numberOfElasticSearchQueries++;
    }

    /**
     * @param string $type
     * @param Log $log
     */
    private function log($type, Log $log)
    {
        $this->logsByType[$type][] = $log;
        $this->logsChronological[] = $log;
    }
}