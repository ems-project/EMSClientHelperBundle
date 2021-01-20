<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

final class Template
{
    public string $name;
    public string $code;

    public const PREFIX = '@EMSCH';

    public function __construct(string $name, string $code)
    {
        $this->name = $name;
        $this->code = $code;
    }

    /**
     * @param array<mixed> $hit
     * @param array<mixed> $mapping
     */
    public static function fromHit(array $hit, array $mapping): self
    {
        $nameProperty = $mapping['name'];
        $codeProperty = $mapping['code'];
        $source = $hit['_source'];

        return new self($source[$nameProperty], $source[$codeProperty] ?? '');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
