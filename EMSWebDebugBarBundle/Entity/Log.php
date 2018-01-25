<?php

namespace EMS\ClientHelperBundle\EMSWebDebugBarBundle\Entity;


class Log
{
    /**
     * @var string
     */
    protected $type;
    
    /**
     * @var array
     */
    protected $messages = [];
    
    /**
     * Constants to indicate the different log types
     */
    const ELASTICSEARCH_LOG = "ElasticSearch";
    
    /**
     * @param type $type
     * @param array $messages
     */
    public function __construct($type, array $messages)
    {
        $this->type = $type;
        $this->messages = $messages;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}