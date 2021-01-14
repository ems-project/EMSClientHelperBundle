<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class TranslationBuilder
{
    /** @var string[] */
    private array $locales;
    private LoggerInterface $logger;
    private ClientRequestManager $manager;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, array $locales)
    {
        $this->locales = $locales;
        $this->logger = $manager->getLogger();
        $this->manager = $manager;
        $this->locales = $locales;
    }

    /**
     * @return string[]
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * @return \Generator|MessageCatalogue[]
     */
    public function buildMessageCatalogues(): \Generator
    {
        foreach ($this->manager->all() as $clientRequest) {
            if (null === $contentType = $clientRequest->getTranslationContentType()) {
                continue;
            }

            if (!$clientRequest->mustBeBind() && !$clientRequest->hasEnvironments()) {
                continue;
            }

            foreach ($this->getMessages($clientRequest, $contentType) as $locale => $messages) {
                $messageCatalogue = new MessageCatalogue($locale);
                $messageCatalogue->add($messages, $clientRequest->getCacheKey());

                yield $messageCatalogue;
            }
        }
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function getMessages(ClientRequest $clientRequest, ContentType $contentType): array
    {
        if (null !== $cache = $contentType->getCache()) {
            return $cache;
        }

        $messages = $this->createMessages($clientRequest, $contentType->getName());
        $contentType->setCache($messages);
        $clientRequest->cacheContentType($contentType);

        return $messages;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function createMessages(ClientRequest $clientRequest, string $type): array
    {
        $messages = [];
        $scroll = $clientRequest->scrollAll([
            'size' => 100,
            'type' => $type,
            'sort' => ['_doc'],
        ], '5s');

        foreach ($scroll as $hit) {
            foreach ($this->locales as $locale) {
                if (isset($hit['_source']['label_'.$locale])) {
                    $messages[$locale][$hit['_source']['key']] = $hit['_source']['label_'.$locale];
                }
            }
        }

        return $messages;
    }
}
