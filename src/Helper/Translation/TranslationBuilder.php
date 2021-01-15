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
    private ClientRequest $clientRequest;
    private LoggerInterface $logger;
    /** @var string[] */
    private array $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, LoggerInterface $logger, array $locales)
    {
        $this->clientRequest = $manager->getDefault();
        $this->logger = $logger;
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
        if (null === $contentType = $this->clientRequest->getTranslationContentType()) {
            return;
        }

        if (!$this->clientRequest->mustBeBind() && !$this->clientRequest->hasEnvironments()) {
            return;
        }

        foreach ($this->getMessages($contentType) as $locale => $messages) {
            $messageCatalogue = new MessageCatalogue($locale);
            $messageCatalogue->add($messages, $this->clientRequest->getCacheKey());

            yield $messageCatalogue;
        }
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function getMessages( ContentType $contentType): array
    {
        if (null !== $cache = $contentType->getCache()) {
            return $cache;
        }

        $messages = $this->createMessages($contentType->getName());
        $contentType->setCache($messages);
        $this->clientRequest->cacheContentType($contentType);

        return $messages;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function createMessages(string $type): array
    {
        $messages = [];
        $scroll = $this->clientRequest->scrollAll([
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
