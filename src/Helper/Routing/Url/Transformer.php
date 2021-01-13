<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing\Url;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Twig\TwigException;
use EMS\CommonBundle\Common\EMSLink;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\Error;

final class Transformer
{
    private ClientRequest $clientRequest;
    private Generator $generator;
    private Environment $twig;
    private LoggerInterface $logger;
    private ?string $template;
    /** @var array<mixed> */
    private array $documents;

    public function __construct(ClientRequest $clientRequest, Generator $generator, Environment $twig, LoggerInterface $logger, ?string $template)
    {
        $this->clientRequest = $clientRequest;
        $this->generator = $generator;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->template = $template;
        $this->documents = [];
    }

    public function getGenerator(): Generator
    {
        return $this->generator;
    }

    /**
     * @param array{ouuid?: string, link_type?: string, content_type?: string, query?: string} $match
     *
     * @return false|string
     */
    public function generate(array $match, ?string $locale = null)
    {
        try {
            $emsLink = EMSLink::fromMatch($match);

            if ('asset' === $emsLink->getLinkType()) {
                return '/file/view/'.$emsLink->getOuuid().'?'.\http_build_query($emsLink->getQuery());
            }

            if (!$emsLink->hasContentType()) {
                throw new \Exception('missing content type');
            }

            $document = $this->getDocument($emsLink);
            if (null === $template = $this->renderTemplate($emsLink, $document, $locale)) {
                throw new \Exception('missing template');
            }

            return $this->generator->prependBaseUrl($emsLink, $template);
        } catch (\Exception $ex) {
            $this->logger->error(\sprintf('%s match (%s)', $ex->getMessage(), \json_encode($match)));

            return false;
        }
    }

    /**
     * @return string|string[]|null
     */
    public function transform(string $content, ?string $locale = null, ?string $baseUrl = null)
    {
        return \preg_replace_callback(EMSLink::PATTERN, function ($match) use ($locale, $baseUrl) {
            //array filter to remove empty capture groups
            $cleanMatch = \array_filter($match);

            if (null === $cleanMatch) {
                return $match[0];
            }

            $generation = $this->generate($cleanMatch, $locale);
            $route = $generation ? $generation : $match[0];

            return $baseUrl.$route;
        }, $content);
    }

    /**
     * @param array<mixed> $document
     */
    private function renderTemplate(EMSLink $emsLink, array $document, ?string $locale = null): ?string
    {
        $context = [
            'id' => $document['_id'],
            'source' => $document['_source'],
            'locale' => ($locale ? $locale : $this->clientRequest->getLocale()),
            'url' => $emsLink,
        ];

        $contentType = $document['_source'][EMSSource::FIELD_CONTENT_TYPE] ?? $document['_type'];
        if ($this->template) {
            $template = \str_replace('{type}', $contentType, $this->template);

            if ($result = $this->twigRender($template, $context)) {
                return $result;
            }
        }

        return $this->twigRender('@EMSCH/routing/'.$contentType, $context);
    }

    /**
     * @param array<mixed> $context
     */
    private function twigRender(string $template, array $context): ?string
    {
        try {
            return $this->twig->render($template, $context);
        } catch (TwigException $ex) {
            $this->logger->warning($ex->getMessage());
        } catch (Error $ex) {
            $this->logger->error($ex->getMessage());
        }

        return null;
    }

    /**
     * @return array<mixed>
     */
    private function getDocument(EMSLink $emsLink): array
    {
        if (isset($this->documents[$emsLink->__toString()])) {
            return $this->documents[$emsLink->__toString()];
        }

        $document = $this->clientRequest->getByOuuid(
            $emsLink->getContentType(),
            $emsLink->getOuuid(),
            [],
            ['*.content', '*.attachement', '*._attachement']
        );

        if (!$document) {
            throw new \Exception('Document not found for : '.$emsLink);
        }
        $this->documents[$emsLink->__toString()] = $document;

        return $document;
    }
}
