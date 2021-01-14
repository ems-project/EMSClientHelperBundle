<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

final class Translator implements CacheWarmerInterface
{
    private TranslationBuilder $builder;
    private TranslatorBagInterface $translator;

    public function __construct(TranslationBuilder $translationBuilder, TranslatorBagInterface $translator)
    {
        $this->builder = $translationBuilder;
        $this->translator = $translator;
    }

    public function addCatalogues(): void
    {
        foreach ($this->builder->buildMessageCatalogues() as $messageCatalogue) {
            $catalogue = $this->translator->getCatalogue($messageCatalogue->getLocale());
            $catalogue->addCatalogue($messageCatalogue);
        }
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp($cacheDir): void
    {
        try {
            $this->addCatalogues();
        } catch (\Throwable $e) {
        }
    }
}
