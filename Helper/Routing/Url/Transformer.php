<?php

namespace EMS\ClientHelperBundle\Helper\Routing\Url;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Twig\TwigException;
use EMS\CommonBundle\Common\EMSLink;

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
            $emsLink = EMSLink::fromMatch($match);

            if ('asset' === $emsLink->getLinkType()) {
                return '/file/view/' . $emsLink->getOuuid() . '?' . http_build_query($emsLink->getQuery());
            }

            if (!$emsLink->hasContentType()) {
                return false;
            }

            $document = $this->getDocument($emsLink);
            $template = $this->renderTemplate($emsLink, $document, $locale);
            $url = $this->generator->prependBaseUrl($emsLink, $template);

            return $url;
        } catch (TwigException $ex) {
            throw $ex;
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
        return preg_replace_callback(EMSLink::PATTERN, function ($match) use ($locale, $baseUrl) {
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
            return $this->twig->render('@EMSCH/routing/'.$document['_type'], [
                'id'     => $document['_id'],
                'source' => $document['_source'],
                'locale' => ($locale?$locale:$this->clientRequest->getLocale()),
                'url'    => $emsLink,
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
