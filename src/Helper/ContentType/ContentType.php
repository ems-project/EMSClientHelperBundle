<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\ContentType;

final class ContentType
{
    private string $alias;
    /** @var string[] */
    private array $names;
    private \DateTimeImmutable $lastPublished;
    private int $total;
    /** @var ?array<mixed> */
    private ?array $cache = null;

    public function __construct(string $alias, string $name, int $total)
    {
        $this->alias = $alias;
        $this->names = [$name];
        $this->total = $total;
        $this->lastPublished = new \DateTimeImmutable();
    }

    public function getName(): string
    {
        return \implode('|', $this->names);
    }

    public function isLastPublishedAfterTime(int $timestamp): bool
    {
        return $this->lastPublished->getTimestamp() <= $timestamp;
    }

    /**
     * Used by the cacheHelper, if the cache contentType has the same cache compare it will be used.
     * Total needs to be included, for deleted documents on a contentType.
     */
    public function getCacheValidityTag(): string
    {
        return \sprintf('%d_%d', $this->getLastPublished()->getTimestamp(), $this->total);
    }

    public function getCacheKey(): string
    {
        return \sprintf('%s_%s', $this->alias, $this->getName());
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

    public function addContentType(ContentType $newType): void
    {
        if (1 !== \count($newType->names)) {
            throw new \RuntimeException('Can not add a non single item ContentType');
        }

        $name = \reset($newType->names);
        if (\in_array($name, $this->names, true)) {
            return;
        }

        if ($this->alias !== $newType->alias) {
            throw new \RuntimeException(\sprintf('Alias mismatched ! %s vs. %s', $this->alias, $newType->alias));
        }

        $this->names[] = $name;
        \sort($this->names);
        $this->total += $newType->total;
        $this->lastPublished = new \DateTimeImmutable();
    }
}
