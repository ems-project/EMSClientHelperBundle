<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSRoutingBundle\EMSLink;

class RoutingService
{
    /**
     * @var ClientRequest
     */
    private $clientRequest;
    
    /**
     * @var UrlHelperService
     */
    private $urlHelperService;
    
    /**
     * @var \Twig_Environment
     */
    private $twig;
    
    /**
     * @param ClientRequest     $clientRequest injected by compiler pass
     * @param UrlHelperService  $urlHelperService
     * @param \Twig_Environment $twig
     */
    public function __construct(
        ClientRequest $clientRequest,
        UrlHelperService $urlHelperService,
        \Twig_Environment $twig
    ) {
        $this->clientRequest = $clientRequest;
        $this->urlHelperService = $urlHelperService;
        $this->twig = $twig;    
    }
    
    /**
     * @return UrlHelperService
     */
    public function getUrlHelperService()
    {
        return $this->urlHelperService;
    }
    
    /**
     * @param array $match [link_type, content_type, ouuid, query]
     */
    public function generate(array $match)
    {
        try {
            $emsLink = new EMSLink($match);
            
            if ($emsLink->isAsset()) {
                return $this->renderAsset($emsLink);
            }
            
            if (!$emsLink->hasContentType()) {
                return false;
            }
            
            $document = $this->getDocument($emsLink);
            
            $template = $this->renderTemplate($emsLink, $document);
            $url = $this->urlHelperService->prependBaseUrl($emsLink, $template);
            
            return $url;
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }  
    }
    
    /**
     * @param EMSLink $emsLink
     * @param array   $document
     * 
     * @return string
     */
    private function renderTemplate(EMSLink $emsLink, array $document)
    {
        try {
            return $this->twig->render($document['_type'].'.ems.twig', [
                'id'     => $document['_id'],
                'source' => $document['_source'],
                'locale' => $this->clientRequest->getLocale(),
                'linkType' => $emsLink->getLinkType(),
            ]);
        } catch (\Twig_Error $ex) {
            return 'Template errror: ' . $ex->getMessage();
        }
    }
    
    /**
     * @param EMSLink $emsLink
     * exemple input: src="ems://asset:c71c8253399e87aaf2d549d00b697adee0664aa9?name=base_service_f.gif&amp;type=image/gif"
     * @return string
     */
    private function renderAsset(EMSLink $emsLink) {
        try {
            return $this->twig->render('ems_asset.ems.twig', [
                    'sha1'     => $emsLink->getOuuid(),
                    'name'     => $emsLink->getQuery()['name'],
                    'type'     => $emsLink->getQuery()['type']
            ]);
        } catch (\Twig_Error $ex) {
            return 'Template errror: ' . $ex->getMessage();
        }
    }
    
    /**
     * @param EMSLink $emsLink
     *
     * @return array|false
     * 
     * @throw \Exception
     */
    private function getDocument(EMSLink $emsLink)
    {
        $document = $this->clientRequest->getByOuuid(
            $emsLink->getContentType(),
            $emsLink->getOuuid()
        );
        
        if (!$document) {
            throw new \Exception('Document not found for : ' . $emsLink);
        }
        
        return $document;
    }
}
