<?php

namespace EMS\ClientHelperBundle\EMSWebDebugBarBundle\Entity;

class ElasticSearchLog extends Log
{
    /**
     * @var string $query
     */
    public function __construct($function, $arguments)
    {   
        parent::__construct(Log::ELASTICSEARCH_LOG, [
            'function' => $function,
            'arguments' => json_encode($arguments),
        ]);
        
    }
    
    /**
     * @return string
     */
    public function getFunction()
    {
        return $this->messages['function'];
    }
    
    /**
     * @return string
     */
    public function getArguments()
    {
        return $this->messages['arguments'];
    }
    
    
}