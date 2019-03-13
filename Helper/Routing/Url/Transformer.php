<?php

namespace EMS\ClientHelperBundle\Helper\Routing\Url;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Twig\TwigException;
use EMS\CommonBundle\Common\EMSLink;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $template;

    /**
     * @param ClientRequest     $clientRequest injected by compiler pass
     * @param Generator         $generator
     * @param \Twig_Environment $twig
     * @param LoggerInterface   $logger
     * @param string            $template
     */
    public function __construct(ClientRequest $clientRequest, Generator $generator, \Twig_Environment $twig, LoggerInterface $logger, ?string $template)
    {
        $this->clientRequest = $clientRequest;
        $this->generator = $generator;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->template = $template;
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
    public function generate(array $match, $locale = null)
    {
        try {
            $emsLink = EMSLink::fromMatch($match);

            if ('asset' === $emsLink->getLinkType()) {
                return '/file/view/' . $emsLink->getOuuid() . '?' . http_build_query($emsLink->getQuery());
            }

            if (!$emsLink->hasContentType()) {
                throw new \Exception('missing content type');
            }

            $document = $this->getDocument($emsLink);
            $template = $this->renderTemplate($emsLink, $document, $locale);
            $url = $this->generator->prependBaseUrl($emsLink, $template);

            return $url;
        } catch (\Exception $ex) {
            $this->logger->error(sprintf('%s match (%s)', $ex->getMessage(), json_encode($match)));
            return false;
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
    private function renderTemplate(EMSLink $emsLink, array $document, $locale = null)
    {
        $context = [
            'id'     => $document['_id'],
            'source' => $document['_source'],
            'locale' => ($locale?$locale:$this->clientRequest->getLocale()),
            'url'    => $emsLink,
        ];

        if ($this->template) {
            $template = str_replace('{type}', $document['_type'], $this->template);

            if ($result = $this->twigRender($template, $context)) {
                return $result;
            }
        }

        return $this->twigRender('@EMSCH/routing/'.$document['_type'], $context);
    }

    /**
     * @param string $template
     * @param array  $context
     *
     * @return string|null
     */
    private function twigRender(string $template, array $context): ?string
    {
        try {
            return $this->twig->render($template, $context);
        } catch (TwigException $ex) {
            $this->logger->warning($ex->getMessage());
        } catch (\Twig_Error $ex) {
            $this->logger->error($ex->getMessage());
        }

        return null;
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
