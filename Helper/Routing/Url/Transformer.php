<?php

namespace EMS\ClientHelperBundle\Helper\Routing\Url;

use EMS\ClientHelperBundle\Helper\Request\ClientRequest;

class Transformer
{
    /**
     * @var ClientRequest
     */
    private $clientRequest;
    
    /**
     * @var Generator
     */
    private $generator;
    
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
     * @param Generator         $generator
     * @param \Twig_Environment $twig
     */
    public function __construct(ClientRequest $clientRequest, Generator $generator, \Twig_Environment $twig)
    {
        $this->clientRequest = $clientRequest;
        $this->generator = $generator;
        $this->twig = $twig;    
    }
    
    /**
     * @return Generator
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @param array  $match [link_type, content_type, ouuid, query]
     * @param string $locale
     *
     * @return false|string
     */
    public function generate(array $match, $locale=null)
    {
        try {
            $emsLink = new EMSUrl($match);

            if (!$emsLink->hasContentType()) {
                return false;
            }
            
            $document = $this->getDocument($emsLink);
            $template = $this->renderTemplate($emsLink, $document, $locale);
            $url = $this->generator->prependBaseUrl($emsLink, $template);

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
     * @param EMSUrl $url
     * @param array  $document
     * @param string $locale
     * 
     * @return string
     */
    private function renderTemplate(EMSUrl $url, array $document, $locale=null)
    {
        try {
            return $this->twig->render('@EMSCH/routing/'.$document['_type'], [
                'id'     => $document['_id'],
                'source' => $document['_source'],
                'locale' => ($locale?$locale:$this->clientRequest->getLocale()),
                'url'    => $url,
            ]);
        } catch (\Twig_Error $ex) {
            return 'Template errror: ' . $ex->getMessage();
        }
    }
    
    /**
     * @param EMSUrl $url
     *
     * @return array|false
     * 
     * @throw \Exception
     */
    private function getDocument(EMSUrl $url)
    {
        $document = $this->clientRequest->getByOuuid(
            $url->getContentType(),
            $url->getOuuid(),
            [],
            ['*.content', '*.attachement', '*._attachement']
        );
        
        if (!$document) {
            throw new \Exception('Document not found for : ' . $url);
        }
        
        return $document;
    }
}
