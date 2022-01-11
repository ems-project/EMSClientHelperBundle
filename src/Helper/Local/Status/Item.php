<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\Status;

final class Item
{
    private string $key;
    private string $id;
    private ?string $idOrigin = null;
    private string $contentType;
    /** @var array<mixed> */
    private array $dataLocal = [];
    /** @var array<mixed> */
    private array $dataOrigin = [];

    private function __construct(string $key, string $contentType)
    {
        $this->key = $key;
        $this->id = \sha1($key);
        $this->contentType = $contentType;
    }

    public function isAdded(): bool
    {
        return $this->id !== $this->idOrigin;
    }

    public function isUpdated(): bool
    {
        if ($this->id !== $this->idOrigin) {
            return false;
        }

        if ([] === $this->dataLocal || $this->dataLocal === $this->dataOrigin) {
            return false;
        }

        return true;
    }

    public function isDeleted(): bool
    {
        if (null === $this->idOrigin) {
            return false;
        }

        return [] === $this->dataLocal || $this->id !== $this->idOrigin;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIdOrigin(): ?string
    {
        return $this->idOrigin;
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
        $item->setIdOrigin($ouuid);

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

    public function setIdOrigin(?string $idOrigin): void
    {
        $this->idOrigin = $idOrigin;
    }
}
