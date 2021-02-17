<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Symfony\Component\Translation\MessageCatalogue;

final class TranslationBuilder extends AbstractBuilder
{
    /**
     * @return \Generator|MessageCatalogue[]
     */
    public function buildMessageCatalogues(Environment $environment)
    {
        if (null === $translations = $this->getTranslations($environment)) {
            return;
        }

        foreach ($this->locales as $locale) {
            $messages = $translations->getMessages($locale);

            $messageCatalogue = new MessageCatalogue($locale);
            $messageCatalogue->add($messages, $environment->getName());

            yield $messageCatalogue;
        }
    }

    public function getTranslations(Environment $environment): ?Translations
    {
        if (null === $contentType = $this->clientRequest->getTranslationContentType($environment)) {
            return null;
        }

        $cache = $contentType->getCache();
        if ($cache instanceof Translations) {
            return $cache;
        }

        $hits = $this->search($contentType, [
            'sort' => ['key' => ['order' => 'asc', 'missing' => '_last', 'unmapped_type' => 'text']],
        ]);

        $translations = Translations::fromHits($hits, $this->locales);
        $contentType->setCache($translations);
        $this->clientRequest->cacheContentType($contentType);

        return $translations;
    }
}
