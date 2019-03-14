<?php

namespace EMS\ClientHelperBundle\Exception;

/**
 */
class MissingTranslationException extends \Exception
{
    protected $AVAILABLE_LANGUAGES = ["fr", "nl", "de", "en"];
    
    private $linkLabels;
    private $ouuid;
    private $linkType;
    
    public function __construct($ouuid, array $linkLabels, $linkType = 'object')
    {
        $this->linkLabels = $linkLabels;
        $this->ouuid = $ouuid;
        $this->linkType = $linkType;
    }
    
    public function getLinkLabels()
    {
        return $this->linkLabels;
    }
    
    public function getOuuid()
    {
        return $this->ouuid;
    }
    
    public function getLinkType()
    {
        return $this->linkType;
    }
}
