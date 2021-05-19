<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\Status;

final class Item
{
    private string $key;
    private ?string $id = null;
    private string $contentType;
    /** @var array<mixed> */
    private array $dataLocal = [];
    /** @var array<mixed> */
    private array $dataOrigin = [];

    private function __construct(string $key, string $contentType)
    {
        $this->key = $key;
        $this->contentType = $contentType;
    }

    public function hasId(): bool
    {
        return null === $this->id;
    }

    public function hasAllData(): bool
    {
        return [] !== $this->dataLocal && [] !== $this->dataOrigin;
    }

    public function hasDataLocal(): bool
    {
        return [] === $this->dataLocal;
    }

    public function dataIsEqual(): bool
    {
        return $this->dataLocal === $this->dataOrigin;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return array<mixed>
     */
    public function getDataLocal(): array
    {
        return $this->dataLocal;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromLocal(string $key, string $contentType, array $data): self
    {
        $item = new self($key, $contentType);
        $item->setDataLocal($data);

        return $item;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromOrigin(string $key, string $contentType, string $ouuid, array $data): self
    {
        $item = new self($key, $contentType);
        $item->setDataOrigin($data);
        $item->id = $ouuid;

        return $item;
    }

    /**
     * @param array<mixed> $dataLocal
     */
    public function setDataLocal(array $dataLocal): void
    {
        \ksort($dataLocal);

        $this->dataLocal = $dataLocal;
    }

    /**
     * @param array<mixed> $dataOrigin
     */
    public function setDataOrigin(array $dataOrigin): void
    {
        \ksort($dataOrigin);

        $this->dataOrigin = $dataOrigin;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }
}
