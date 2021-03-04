<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\Builder\BuilderDocumentInterface;
use EMS\CommonBundle\Elasticsearch\Document\DocumentInterface;

final class TranslationDocument implements BuilderDocumentInterface
{
    private string $id;
    /** @var array<mixed> */
    private array $source;
    /** @var string[] */
    private array $locales;

    public function __construct(DocumentInterface $document, array $locales)
    {
        $this->id = $document->getId();
        $this->source = $document->getSource();
        $this->locales = $locales;
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
        return $this->source['key'];
    }

    public function getDataSource(): array
    {
        $source = [];

        foreach ($this->locales as $locale) {
            if (isset($this->source['label_'.$locale])) {
                $source['label_'.$locale] = $this->source['label_'.$locale];
            }
        }

        return $source;
    }

    /**
     * @return array<string, null|string>
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