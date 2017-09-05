<?php

namespace EMS\ClientHelperBundle\EMSLanguageSelectionBundle\Service;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;

class LanguageService
{
    /**
     * @var ClientRequest
     */
    protected $clientRequest;

    /**
     * @var array
     */
    private $supportedLocale;

    /**
     * @var string
     */
    private $optionType;

    /**
     * @var string
     */
    private $emschTransDomain;

    /**
     * @param array $config
     */
    public function setConfig ($config)
    {
        $this->supportedLocale = $config[ 'supported_locale' ];
        $this->optionType = $config[ 'option_type' ];
        $this->emschTransDomain = $config[ 'emsch_trans_domain' ];
    }

    /**
     * @param ClientRequest $clientRequest
     */
    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    public function getSupportedLanguages()
    {
        $languages = [];

        foreach ($this->supportedLocale as $locale) {
            $languages[] = $locale['locale'];
        }

        return $languages;
    }

    /**
     * @return string
     */
    public function getTranslationDomain()
    {
        return $this->emschTransDomain;
    }

    /**
     * @param string $id
     *
     * @return boolean
     */
    public function getOption($id)
    {
        try {
            $option = $this->clientRequest->get($this->optionType, $id);
            
            return $option['_source']['activated'];
        } catch (Missing404Exception $ex) {
            return false;
        }
    }
    
    /**
     * @return array
     */
    public function getLanguages()
    {
        $languages = [];
                
        foreach ($this->supportedLocale as $locale) {
            if (!$this->getOption('language_select_'.$locale['locale'])) {
                continue;
            }
            $languages[] = $locale['locale'];
        }
        
        return $languages;       
    }
    
    /**
     * @return array
     */
    public function getLanguageSelections()
    {
        $selections = [];

        foreach ($this->supportedLocale as $locale) {
            if (!$this->getOption('language_select_'.$locale['locale'])) {
                continue;
            }
            $selections[$locale['locale']] = [
                'logo' => $locale['logo_path']
            ];
        }
        
        return $selections;
    }
}
