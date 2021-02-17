<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

final class Translations
{
    /** @var Translation[] */
    private array $list = [];

    /**
     * @param array<mixed> $hits
     * @param string[]     $locales
     */
    public static function fromHits(array $hits, array $locales): self
    {
        $translations = new self();
        $translations->list = array_map(fn (array $hit) => Translation::fromHit($hit, $locales), $hits);

        return $translations;
    }

    public function getMessages(string $locale): array
    {
        $messages = [];

        foreach ($this->list as $translation) {
            if ($translation->hasMessage($locale)) {
                $messages[$translation->getKey()] = $translation->getMessage($locale);
            }
        }

        return $messages;
    }
}