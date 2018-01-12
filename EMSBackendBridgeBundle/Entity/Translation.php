<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity;

class Translation 
{
    private $modifiedDate;
    
    const MODIFIED_DATE = 'modified_date';
    
    public function __construct($source)
    {
        if (isset($source[self::MODIFIED_DATE])) {
            $this->modifiedDate = $source[self::MODIFIED_DATE];
        }
    }
    
    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }
}