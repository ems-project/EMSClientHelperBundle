<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\ChangeList;

final class ChangeListItem
{
    private string $key;
    private ?string $originOuuid;
    private array $originRaw = [];
    private array $localRaw = [];

    private function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function fromOrigin(string $key, string $ouuid, array $raw): self
    {
        $item = new self($key);
        $item->originOuuid = $ouuid;
        $item->originRaw = $raw;

        return $item;
    }

    public function setLocalRaw(array $raw): void
    {
        $this->localRaw = $raw;
    }
}