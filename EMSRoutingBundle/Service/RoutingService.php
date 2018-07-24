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
     * Regex for searching ems links in content
     * content_type and query can be empty/optional
     *
     * Regex101.com:
     * ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\'|\s]*)))?
     *
     * Example: <a href="ems://object:page:AV44kX4b1tfmVMOaE61u">example</a>
     * link_type => object, content_type => page, ouuid => AV44kX4b1tfmVMOaE61u
     */
    const EMS_LINK = '/ems:\/\/(?P<link_type>.*?):(?:(?P<content_type>.*?):)?(?P<ouuid>([[:alnum:]]|-|_)*)(?:\?(?P<query>(?:[^"|\'|\s]*)))?/';

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
     * @param string $locale
     */
    public function generate(array $match, $locale=null)
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
            
            $template = $this->renderTemplate($emsLink, $document, $locale);
            $url = $this->urlHelperService->prependBaseUrl($emsLink, $template);
            
            return $url;
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }  
    }

    /**
     * @param string $content
     * @param string $locale
     * @param string $baseUrl
     *
     * @return null|string|string[]
     */
    public function transform($content, $locale = null, $baseUrl = null)
    {
        return preg_replace_callback(self::EMS_LINK, function ($match) use ($locale, $baseUrl) {
            //array filter to remove empty capture groups
            $generation = $this->generate(array_filter($match), $locale);
            $route = $generation ? $generation : $match[0];

            return $baseUrl . $route;
        }, $content);
    }
    
    /**
     * @param EMSLink $emsLink
     * @param array   $document
     * @param string  $locale
     * 
     * @return string
     */
    private function renderTemplate(EMSLink $emsLink, array $document, $locale=null)
    {
        try {
            return $this->twig->render($document['_type'].'.ems.twig', [
                'id'     => $document['_id'],
                'source' => $document['_source'],
                'locale' => ($locale?$locale:$this->clientRequest->getLocale()),
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
            $emsLink->getOuuid(),
            [],
            ['*.content', '*.attachement', '*._attachement']
        );
        
        if (!$document) {
            throw new \Exception('Document not found for : ' . $emsLink);
        }
        
        return $document;
    }
}
