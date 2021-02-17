<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

final class Translation
{
    private string $key;
    private ?string $ouuid;
    private array $messages = [];

    private function __construct(string $key)
    {
        $this->key = $key;
    }

    public function hasMessage($locale): bool
    {
        return isset($this->messages[$locale]);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getMessage($locale): string
    {
        return $this->messages[$locale];
    }

    public static function fromHit(array $hit, array $locales): self
    {
        $source = $hit['_source'];

        $message = new self($source['key']);
        $message->ouuid = $hit['_id'];

        foreach ($locales as $locale) {
            if (isset($source['label_'.$locale])) {
                $message->messages[$locale] = $hit['_source']['label_'.$locale];
            }
        }

        return $message;
    }



}