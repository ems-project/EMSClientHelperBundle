<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class TranslationBuilder extends AbstractBuilder
{
    /** @var string[] */
    private array $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, LoggerInterface $logger, array $locales)
    {
        parent::__construct($manager, $logger);
        $this->locales = $locales;
    }

    /**
     * @return \Generator|MessageCatalogue[]
     */
    public function buildMessageCatalogues(Environment $environment): \Generator
    {
        if (null === $contentType = $this->clientRequest->getTranslationContentType($environment)) {
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
    private function getMessages(ContentType $contentType): array
    {
        if (null !== $cache = $contentType->getCache()) {
            return $cache;
        }

        $messages = $this->createMessages($contentType);
        $contentType->setCache($messages);
        $this->clientRequest->cacheContentType($contentType);

        return $messages;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    private function createMessages(ContentType $contentType): array
    {
        $messages = [];

        $hits = $this->searchMessages($contentType);

        foreach ($hits as $hit) {
            foreach ($this->locales as $locale) {
                if (isset($hit['_source']['label_'.$locale])) {
                    $messages[$locale][$hit['_source']['key']] = $hit['_source']['label_'.$locale];
                }
            }
        }

        return $messages;
    }

    /**
     * @return array<mixed>
     */
    private function searchMessages(ContentType $contentType): array
    {
        return $this->search($contentType, [
            'sort' => [
                'key' => [
                    'order' => 'asc',
                    'missing' => '_last',
                    'unmapped_type' => 'text',
                ],
            ],
        ]);
    }
}
