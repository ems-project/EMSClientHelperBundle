<?php

namespace EMS\ClientHelperBundle\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatorInterface;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Request\RequestHelper;

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
     * @var RequestHelper
     */
    private $requestHelper;
    
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
     * @param string              $cachePath
     * @param TranslatorInterface $translator
     * @param RequestHelper       $requestHelper
     * @param ClientRequest       $clientRequest
     * @param string              $translationType
     */
    public function __construct(
            $cachePath, 
            TranslatorInterface $translator,
            RequestHelper $requestHelper,
            ClientRequest $clientRequest,
            $translationType
    )
    {
        $this->cachePath = $cachePath;
        $this->translationCachePath = $cachePath . DIRECTORY_SEPARATOR . 'translations';
        
        $this->translator = $translator;
        $this->requestHelper = $requestHelper;
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
     * @return \DateTime
     */
    private function getLastTranslationDateInCache()
    {
        if(!file_exists($this->translationCachePath)) {
            return null;
        }
            
        $domain = $this->requestHelper->getEnvironment();
        $datestr = $this->translator->trans(self::TIMESTAMP_PREFIX . $domain);
        
        if($datestr !== self::TIMESTAMP_PREFIX . $domain){            
            return new \DateTime($datestr);
        }
        //date not defined yet
        return null;
    }
    
    /**
     * @return \DateTime
     */
    private function getLastTranslationChangeDate()
    {
        $body = [
                'sort' => [
                    '_published_datetime' => [
                        'order' => 'desc',
                        'missing' => '_last'
                    ]
                ],
               '_source' => '_published_datetime'
        ];
        
        $result = $this->clientRequest->search($this->translationType, $body, 0, 1);

        if ($result['hits']['total'] > 0 && isset($result['hits']['hits']['0']['_source']['_published_datetime'])) {
            return new \DateTime($result['hits']['hits']['0']['_source']['_published_datetime']);
        }
        return null;
    }

}