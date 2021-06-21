<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing\Url;

use EMS\ClientHelperBundle\Exception\TemplatingException;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Common\EMSLink;
use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Twig\RequestRuntime;
use Psr\Log\LoggerInterface;
use Twig\Environment;

final class Transformer
{
    private RequestRuntime $requestRuntime;
    private ClientRequest $clientRequest;
    private Generator $generator;
    private Environment $twig;
    private LoggerInterface $logger;
    private string $template;
    /** @var array<string, mixed> */
    private array $documents;

    public function __construct(RequestRuntime $requestRuntime, ClientRequestManager $clientRequestManager, Generator $generator, Environment $twig, LoggerInterface $logger, ?string $template)
    {
        $this->requestRuntime = $requestRuntime;
        $this->clientRequest = $clientRequestManager->getDefault();
        $this->generator = $generator;
        $this->twig = $twig;
        $this->logger = $logger;
        $this->template = $template ?? '@EMSCH/template/{type}.ems_link.twig';
        $this->documents = [];
    }

    public function getGenerator(): Generator
    {
        return $this->generator;
    }

    /**
     * @param array<mixed> $match
     * @param array<mixed> $config
     */
    private function generate(array $match, array $config = []): ?string
    {
        try {
            $emsLink = EMSLink::fromMatch($match);

            if ('asset' === $emsLink->getLinkType()) {
                return $this->requestRuntime->assetPath([
                    EmsFields::CONTENT_FILE_HASH_FIELD => $emsLink->getOuuid(),
                    EmsFields::CONTENT_FILE_NAME_FIELD => $emsLink->getQuery()['name'] ?? 'asset',
                    EmsFields::CONTENT_MIME_TYPE_FIELD => $emsLink->getQuery()['type'] ?? 'application/octet-stream',
                ]);
            }

            if (!$emsLink->hasContentType()) {
                throw new \Exception('missing content type');
            }

            $context = $this->makeContext($emsLink, $config);
            $template = \str_replace('{type}', $emsLink->getContentType(), $this->template);
            $url = $this->twigRender($template, $context);

            if ($url) {
                return $this->generator->prependBaseUrl($url);
            }

            return null;
        } catch (\Exception $ex) {
            $this->logger->error(\sprintf('%s match (%s)', $ex->getMessage(), \json_encode($match)));

            return null;
        }
    }

    /**
     * @param array<mixed> $config
     */
    public function transform(string $content, array $config = []): string
    {
        $transform = \preg_replace_callback(EMSLink::PATTERN, function ($match) use ($config) {
            //array filter to remove empty capture groups
            $cleanMatch = \array_filter($match);

            if (null === $cleanMatch) {
                return $match[0];
            }

            $generation = $this->generate($cleanMatch, $config);
            $route = (null !== $generation ? $generation : $match[0]);
            $baseUrl = $config['baseUrl'] ?? '';

            return $baseUrl.$route;
        }, $content);

        return \is_string($transform) ? $transform : $content;
    }

    /**
     * @param array<mixed> $context
     */
    private function twigRender(string $template, array $context): ?string
    {
        try {
            return $this->twig->render($template, $context);
        } catch (TemplatingException $ex) {
            $this->logger->warning($ex->getMessage());
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
        }

        return null;
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    private function makeContext(EMSLink $emsLink, array $config): array
    {
        $context = $config['context'] ?? [];
        $context['url'] = $emsLink;

        $dynamicTypes = $config['dynamic_types'] ?? [];
        if (!\in_array($emsLink->getContentType(), $dynamicTypes)) {
            if ($document = $this->getDocument($emsLink)) {
                $context['id'] = $document['_id'];
                $context['source'] = $document['_source'];
            }
        }

        if (isset($config['locale'])) {
            $context['locale'] = $config['locale'];
        }
        if (!isset($context['locale'])) {
            $context['locale'] = $this->clientRequest->getLocale();
        }

        return $context;
    }

    /**
     * @return array<mixed>
     */
    private function getDocument(EMSLink $emsLink): ?array
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
