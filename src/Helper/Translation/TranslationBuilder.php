<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\Builder\BuilderDocumentInterface;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\CommonBundle\Search\Search;
use Symfony\Component\Translation\MessageCatalogue;

final class TranslationBuilder extends AbstractBuilder
{
    /**
     * @return \Generator|MessageCatalogue[]
     */
    public function buildMessageCatalogues(Environment $environment): \Generator
    {
        if (null === $contentType = $this->getContentType($environment)) {
            return [];
        }

        foreach ($this->getMessages($contentType) as $locale => $messages) {
            $messageCatalogue = new MessageCatalogue($locale);
            $messageCatalogue->add($messages, $environment->getName());

            yield $messageCatalogue;
        }
    }

    public function buildFiles(Environment $environment, string $directory): void
    {
        $messageCatalogues = $this->buildMessageCatalogues($environment);

        TranslationFiles::build($directory, $messageCatalogues);
    }

    public function getContentType(Environment $environment): ?ContentType
    {
        return $this->clientRequest->getTranslationContentType($environment);
    }

    /**
     * @return BuilderDocumentInterface[]|TranslationDocument[]
     */
    public function getDocuments(Environment $environment): array
    {
        if (null === $contentType = $this->getContentType($environment)) {
            return [];
        }

        return $this->searchDocuments($contentType);
    }

    protected function modifySearch(Search $search): void
    {
        $search->setSort(['key' => ['order' => 'asc', 'missing' => '_last', 'unmapped_type' => 'text']]);
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

        foreach ($this->searchDocuments($contentType) as $document) {
            foreach ($document->getMessages() as $locale => $message) {
                if (null !== $message) {
                    $messages[$locale][$document->getName()] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * @return TranslationDocument[]
     */
    private function searchDocuments(ContentType $contentType): array
    {
        $documents = [];

        foreach ($this->search($contentType)->getDocuments() as $document) {
            $translationDocument = new TranslationDocument($document, $this->locales);
            $documents[$translationDocument->getName()] = $translationDocument;
        }

        return $documents;
    }
}
