<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorInterface;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;

class ClearCacheService
{
    /**
     * @var string
     */
    private $cachePath;
    
    /**
     * @var TranslatorInterface
     */
    private $translator;
    
    /**
     * @var RequestService
     */
    private $requestService;
    
    /**
     * @var ClientRequest
     */
    private $clientRequest;
    
    /**
     * @var string
     */
    private $translationType;
    
    /**
     * @var string
     */
    private $translationCachePath;
    
    const TIMESTAMP_PREFIX = 'cache_timestamp_';
        
    /**
     * @param string $cachePath
     * @param TranslatorInterface $translator
     * @param RequestService $requestService
     * @param ClientRequest $clientRequest
     * @param string $translationType
     */
    public function __construct(
            $cachePath, 
            TranslatorInterface $translator,
            RequestService $requestService,
            ClientRequest $clientRequest,
            $translationType
    )
    {
        $this->cachePath = $cachePath;
        $this->translationCachePath = $cachePath . '\translations';
        
        $this->translator = $translator;
        $this->requestService = $requestService;
        $this->clientRequest = $clientRequest;
        $this->translationType = $translationType;
    }
    
    /**
     * @return void
     */
    public function clearTranslations()
    {
        $finder = new Finder;
        
        foreach ($finder->files()->in($this->translationCachePath) as $file) {
            unlink($file->getRealpath());
        }
        
    }
    
    /**
     * @return boolean
     */
    public function isTranslationCacheFresh()
    {
        $fileDate = $this->getLastTranslationDateInCache();
        $stashDate = $this->getLastTranslationChangeDate();
        
        if ($fileDate === null) {
            return true;
        }

        return $fileDate >= $stashDate;
    }
    
    /**
     * @return DateTime
     */
    private function getLastTranslationDateInCache()
    {
        $domain = $this->requestService->getEnvironment();
        $datestr = $this->translator->trans(self::TIMESTAMP_PREFIX . $domain);
        return new \DateTime($datestr);
    }
    
    /**
     * @return DateTime
     */
    private function getLastTranslationChangeDate()
    {
        $body = [
                'sort' => [
                    'modified_date' => [
                        'order' => 'desc',
                        'missing' => '_last'
                    ]
                ],
               '_source' => 'modified_date'
        ];
        
        $result = $this->clientRequest->search($this->translationType, $body, 0, 1);

        if ($result['hits']['total'] > 0 && isset($result['hits']['hits']['0']['_source']['modified_date'])) {
            return new \DateTime($result['hits']['hits']['0']['_source']['modified_date']);
        }
        return null;
    }

}