<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\Builder\BuilderDocumentInterface;
use EMS\CommonBundle\Elasticsearch\Document\DocumentInterface;

final class TranslationDocument implements BuilderDocumentInterface
{
    private readonly string $id;
    /** @var array<mixed> */
    private array $source;

    /**
     * @param string[] $locales
     */
    public function __construct(DocumentInterface $document, private readonly array $locales)
    {
        $this->id = $document->getId();
        $this->source = $document->getSource();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContentType(): string
    {
        return $this->source['_contenttype'];
    }

    public function getName(): string
    {
        return (string) $this->source['key'];
    }

    /**
     * @return array<mixed>
     */
    public function getDataSource(): array
    {
        $source = ['key' => $this->source['key']];

        foreach ($this->locales as $locale) {
            if (isset($this->source['label_'.$locale])) {
                $source['label_'.$locale] = $this->source['label_'.$locale];
            }
        }

        return $source;
    }

    /**
     * @return array<string, string|null>
     */
    public function getMessages(): array
    {
        $messages = [];

        foreach ($this->locales as $locale) {
            $messages[$locale] = $this->source['label_'.$locale] ?? null;
        }

        return $messages;
    }
}
