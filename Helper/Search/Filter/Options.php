<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Search\Filter;

final class Options
{
    /** @var null|int */
    private $aggSize;
    /** @var string */
    private $field;
    /** @var null|string */
    private $nestedPath;
    /** @var bool if not all doc contain the filter */
    private $optional;
    /** @var bool only public filters will handle a request. */
    private $public;
    /** @var null|bool */
    private $postFilter;
    /** @var null|string */
    private $sortField;
    /** @var string */
    private $sortOrder = 'asc';
    /** @var string */
    private $type;
    /** @var bool */
    private $reversedNested;
    /** @var mixed */
    private $value;

    public function __construct(array $options)
    {
        $this->aggSize = isset($options['aggs_size']) ? (int) $options['aggs_size'] : null;
        $this->field = $options['field'];
        $this->nestedPath = $options['nested_path'] ?? null;
        $this->optional = isset($options['optional']) ? (bool) $options['optional'] : false;
        $this->public = isset($options['public']) ? (bool) $options['public'] : true;
        $this->postFilter = isset($options['post_filter']) ? (bool) $options['post_filter'] : null;
        $this->sortField = isset($options['sort_field']) ? $options['sort_field'] : null;
        $this->sortOrder = isset($options['sort_order']) ? $options['sort_order'] : 'asc';
        $this->type = $options['type'];
        $this->reversedNested = isset($options['reversed_nested']) ? $options['reversed_nested'] : false;
        $this->value = $options['value'] ?? null;
    }

    public function getAggSize(): ?int
    {
        return $this->aggSize;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getNestedPath(): ?string
    {
        return $this->nestedPath;
    }

    public function getPostFilter(): ?bool
    {
        return $this->postFilter;
    }

    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function hasAggSize(): bool
    {
        return $this->aggSize !== null;
    }

    public function hasNestedPath(): bool
    {
        return $this->nestedPath !== null;
    }

    public function hasPostFilter(): bool
    {
        return $this->postFilter !== null;
    }

    public function hasSortField(): bool
    {
        return $this->sortField !== null;
    }

    public function hasValue(): bool
    {
        return $this->value !== null;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function isReversedNested(): bool
    {
        return $this->reversedNested;
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }
}
