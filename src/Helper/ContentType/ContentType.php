<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\ContentType;

final class ContentType
{
    private string $alias;
    private string $name;
    private \DateTimeImmutable $lastPublished;
    private int $total;
    /** @var ?array<mixed> */
    private ?array $cache = null;

    public function __construct(string $alias, string $name, int $total)
    {
        $this->alias = $alias;
        $this->name = $name;
        $this->total = $total;
        $this->lastPublished = new \DateTimeImmutable();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isLastPublishedAfterTime(int $timestamp): bool
    {
        return $this->lastPublished->getTimestamp() <= $timestamp;
    }

    /**
     * Used by the cacheHelper, if the cache contentType has the same cache compare it will be used.
     * Total needs to be included, for deleted documents on a contentType.
     *
     * Also used in the formBundle for invalidating formConfig cache.
     */
    public function getCacheValidityTag(): string
    {
        return \sprintf('%d_%d', $this->getLastPublished()->getTimestamp(), $this->total);
    }

    public function getCacheKey(): string
    {
        return \sprintf('%s_%s', $this->alias, $this->name);
    }

    /**
     * @return ?array<mixed>
     */
    public function getCache(): ?array
    {
        return $this->cache;
    }

    /**
     * @param ?array<mixed> $cache
     */
    public function setCache(?array $cache): void
    {
        $this->cache = $cache;
    }

    public function getLastPublished(): \DateTimeImmutable
    {
        return $this->lastPublished;
    }

    public function setLastPublishedValue(string $lastPublishedValue): void
    {
        $lastPublished = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $lastPublishedValue);

        if ($lastPublished) {
            $this->lastPublished = $lastPublished;
        }
    }
}
