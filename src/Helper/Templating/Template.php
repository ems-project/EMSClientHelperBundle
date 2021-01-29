<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

final class Template
{
    private string $id;
    private string $contentType;
    private string $name;
    private string $code;

    public const PREFIX = '@EMSCH';

    public function __construct(string $id, string $contentType, string $name, string $code)
    {
        $this->id = $id;
        $this->contentType = $contentType;
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

        $id = $hit['_id'];
        $contentType = $hit['_source']['_contenttype'];
        $name = $hit['_source'][$nameProperty];
        $code = $hit['_source'][$codeProperty] ?? '';

        return new self($id, $contentType, $name, $code);
    }

    public function getEmschNameId(): string
    {
        return \sprintf('%s/%s:%s', self::PREFIX, $this->contentType, $this->id);
    }

    public function getEmschName(): string
    {
        return \sprintf('%s/%s', $this->contentType, $this->name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
