<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Service;

use EMS\ClientHelperBundle\EMSRoutingBundle\EMSLink;
use Symfony\Component\Routing\RouterInterface;

class UrlHelperService
{
    /**
     * @var string
     */
    private $baseUrl = '';
    
    /**
     * @var string
     */
    private $phpApp = '';
    
    /**
     * @var array
     */
    private $config = [];
    
    /**
     * Regex for getting the base URL without the phpApp
     * So we can relative link to other applications
     */
    const REGEX_BASE_URL = '/^(?P<baseUrl>\/.*?)(?:(?P<phpApp>\/[\-_A-Za-z0-9]*.php)|\/|)$/i';
    
    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        preg_match(self::REGEX_BASE_URL, $router->getContext()->getBaseUrl(), $match);
        
        if (isset($match['baseUrl'])) {
            $this->baseUrl = $match['baseUrl'];
        }

        if (isset($match['phpApp'])) {
            $this->phpApp = $match['phpApp'];
        }
    }
    
    /**
     * @param string $relativePath
     * @param string $path
     *
     * @return string
     */
    public function createUrl($relativePath, $path)
    {
        return $this->baseUrl . $relativePath . $this->phpApp . $path;
    }
    
    /**
     * @param EMSLink $emsLink
     * @param string  $url
     *
     * @return string
     */
    public function prependBaseUrl(EMSLink $emsLink, $url)
    {
        $path = $this->getRelativePath($emsLink->getContentType());
        
        return $path . $this->phpApp . $url;
    }
    
    /**
     * Called by compiler pass
     * 
     * @param array $config
     * 
     * @see LoadConfigs
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * @param string $contentType
     *
     * @return string
     */
    private function getRelativePath($contentType)
    {
        $relativePaths = $this->config['relative_paths'];
        
        foreach ($relativePaths as $value) {
            if (preg_match($value['regex'], $contentType)) {
                return $value['path'];
            }
        }
        
        return $this->baseUrl;
    }
   
}
