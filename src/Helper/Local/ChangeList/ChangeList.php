<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\ChangeList;

final class ChangeList
{
    /** @var array<string, ChangeListItem> */
    private array $items;

    public function addOrigin(string $key, string $ouuid, array $source): void
    {
        $this->items[$key] = ChangeListItem::fromOrigin($key, $ouuid, $source);
    }

    public function addLocal(string $key, array $source): void
    {
        if (null === $item = $this->getItem($key)) {
            throw new \Exception("jajaja enkel local");
        }

        $item->setLocalRaw($source);
    }

    private function getItem(string $key): ?ChangeListItem
    {
        return $this->items[$key];
    }
}