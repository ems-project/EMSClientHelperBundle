<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\SingleResultException;

class LanguageSelectionService
{
    /**
     * @var ClientRequest
     */
    private $clientRequest;

    /**
     * @var array
     */
    private $supportedLocale;

    /**
     * @var string
     */
    private $optionType;

    /**
     * @param ClientRequest $clientRequest
     * @param array         $supportedLocale
     * @param string        $optionType
     */
    public function __construct(ClientRequest $clientRequest, array $supportedLocale, $optionType)
    {
        $this->clientRequest = $clientRequest;
        $this->supportedLocale = $supportedLocale;
        $this->optionType = $optionType;
    }

    /**
     * @return ClientRequest
     */
    public function getClient()
    {
        return $this->clientRequest;
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
        } catch (SingleResultException $e) {
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