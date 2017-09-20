<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use Symfony\Component\Routing\RouterInterface;

class RoutingService
{
    /**
     * @var ClientRequest
     */
    private $clientRequest;
    
    /**
     * @var RouterInterface
     */
    private $router;
    
    /**
     * @var \Twig_Environment
     */
    private $twig;
    
    /**
     * @var array [][regex, path]
     */
    private $configPaths;
    
    /**
     * Regex for getting the base URL without the phpApp
     * So we can relative link to other applications
     */
    const REGEX_BASE_URL = '/^(?P<baseUrl>\/.*?)(?:(?P<phpApp>\/[\-_A-Za-z0-9]*.php)|\/|)$/i';
    
    /**
     * @param ClientRequest     $clientRequest injected by compiler pass
     * @param RouterInterface   $router
     * @param \Twig_Environment $twig
     */
    public function __construct(
        ClientRequest $clientRequest,
        RouterInterface $router,
        \Twig_Environment $twig,
        array $configPaths
    ) {
        $this->clientRequest = $clientRequest;
        $this->router = $router;
        $this->twig = $twig;
        $this->configPaths = $configPaths;
    }
    
    /**
     * @param string $linkType
     * @param string $contentType
     * @param string $ouuid
     * 
     * @return false|string
     */
    public function generate($linkType, $contentType, $ouuid)
    {
        try {
            if (!$document = $this->getDocument($contentType, $ouuid)) {
                return false;
            }
            
            $template = $this->renderTemplate($document, $linkType);
            
            return $this->getBaseUrl($contentType) . $template;
        } catch (\Twig_Error $ex) {
            return 'Template errror: ' . $ex->getMessage();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    /**
     * @param array  $document
     * @param string $linkType
     * 
     * @return string
     * 
     * @throws \Twig_Error
     */
    private function renderTemplate(array $document, $linkType)
    {
        return $this->twig->render($document['_type'], [
            'id'     => $document['_id'],
            'source' => $document['_source'],
            'locale' => $this->clientRequest->getLocale(),
            'linkType' => $linkType,
        ]);
    }
    
    /**
     * @param string $type
     * @param string $ouuid
     *
     * @return array|false
     */
    private function getDocument($type, $ouuid)
    {
        return $this->clientRequest->getByOuuid($type, $ouuid);
    }
        
    /**
     * @param string $contentType
     * 
     * @return string
     */
    private function getBaseUrl($contentType)
    {
        $baseUrl = $this->router->getContext()->getBaseUrl();
        $match = ['phpApp' => ''];
        
        preg_match(self::REGEX_BASE_URL, $baseUrl, $match);
        
        return $match['baseUrl'] . $this->getPath($contentType) . $match['phpApp'];
    }
    
    /**
     * @param string $contentType
     *
     * @return string
     */
    private function getPath($contentType)
    {
        foreach ($this->configPaths as $configPath) {
            if (\preg_match($configPath['regex'], $contentType)) {
                return $configPath['path'];
            }
        }
        
        return '';
    }
}
