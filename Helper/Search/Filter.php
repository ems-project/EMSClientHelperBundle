<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use Symfony\Component\HttpFoundation\Request;

class Filter
{
    /** @var ClientRequest */
    private $clientRequest;
    /** @var string */
    private $name;
    /** @var string */
    private $type;
    /** @var string */
    private $field;
    /** @var string */
    private $secondaryField;
    /** @var string|null */
    private $nestedPath;

    /** @var ?string */
    private $sortField;
    /** @var string */
    private $sortOrder;
    /** @var bool */
    private $reversedNested;

    /** @var int|null */
    private $aggSize;
    /** @var bool default true for terms, when value passed default false */
    private $postFilter;
    /** @var bool only public filters will handle a request. */
    private $public;
    /** @var bool if not all doc contain the filter */
    private $optional;
    /** @var array */
    private $queryFilters = [];
    /** @var array */
    private $queryTypes = [];

    /** @var array<mixed>|null */
    private $query = [];

    /** @var array|string|null */
    private $value;
    /** @var array */
    private $choices = [];
    /** @var bool|string */
    private $dateFormat;

    const TYPE_TERM = 'term';
    const TYPE_TERMS = 'terms';
    const TYPE_DATE_RANGE = 'date_range';
    const TYPE_DATE_VERSION = 'date_version';

    const TYPES = [
        self::TYPE_TERM,
        self::TYPE_TERMS,
        self::TYPE_DATE_RANGE,
        self::TYPE_DATE_VERSION,
    ];

    public function __construct(ClientRequest $clientRequest, string $name, array $options)
    {
        $this->clientRequest = $clientRequest;

        if (!\in_array($options['type'], self::TYPES)) {
            throw new \Exception(\sprintf('invalid filter type %s', $options['type']));
        }

        $this->name = $name;
        $this->type = $options['type'];
        $this->field = $options['field'] ?? $name;
        $this->secondaryField = $options['secondary_field'] ?? null;
        $this->nestedPath = $options['nested_path'] ?? null;

        $this->public = isset($options['public']) ? (bool) $options['public'] : true;
        $this->optional = isset($options['optional']) ? (bool) $options['optional'] : false;
        $this->aggSize = isset($options['aggs_size']) ? (int) $options['aggs_size'] : null;
        $this->sortField = isset($options['sort_field']) ? $options['sort_field'] : null;
        $this->sortOrder = isset($options['sort_order']) ? $options['sort_order'] : 'asc';
        $this->reversedNested = isset($options['reversed_nested']) ? $options['reversed_nested'] : false;
        $this->dateFormat = isset($options['date_format']) ? $options['date_format'] : 'd-m-Y H:i:s';
        $this->setPostFilter($options);

        if (isset($options['value'])) {
            $this->setQuery($options['value']);
        }
    }

    public function getName(): string
    {
        return $this->name;
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

    public function getField(): string
    {
        return $this->isNested() ? $this->nestedPath.'.'.$this->field : $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function hasAggSize(): bool
    {
        return null !== $this->aggSize;
    }

    public function getAggSize(): ?int
    {
        return $this->aggSize;
    }

    public function isActive(): bool
    {
        return !empty($this->query);
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getQuery(): ?array
    {
        if ($this->optional && self::TYPE_DATE_VERSION !== $this->type) {
            return $this->getQueryOptional($this->getField(), $this->query);
        }

        return $this->query;
    }

    public function isPostFilter(): bool
    {
        return $this->postFilter;
    }

    public function handleRequest(Request $request): void
    {
        if (null !== $this->field) {
            $this->field = \str_replace('%locale%', $request->getLocale(), $this->field);
        }
        $requestValue = $request->get($this->name);

        if ($this->public && $requestValue) {
            $this->setQuery($requestValue);
        } elseif (null !== $this->value) {
            $this->setQuery($this->value);
        }
    }

    public function handleAggregation(array $aggregation, array $types = [], array $queryFilters = [])
    {
        $this->queryTypes = $types;
        $this->queryFilters = $queryFilters;
        $this->setChoices();

        $data = $aggregation['nested'] ?? $aggregation;
        $buckets = $data['filtered_'.$this->name]['buckets'] ?? $data['buckets'];

        foreach ($buckets as $bucket) {
            if (!isset($this->choices[$bucket['key']])) {
                continue;
            }
            $this->choices[$bucket['key']]['filter'] = $bucket['doc_count'];

            if (!isset($bucket['reversed_nested'])) {
                continue;
            }

            $this->choices[$bucket['key']]['reversed_nested'] = $bucket['reversed_nested']['doc_count'];
        }
    }

    public function isChosen(string $choice): bool
    {
        if (!isset($this->choices[$choice])) {
            return false;
        }

        return $this->choices[$choice]['active'];
    }

    public function getChoices(): array
    {
        $this->setChoices();

        return $this->choices;
    }

    public function isNested(): bool
    {
        return null !== $this->nestedPath;
    }

    public function getNestedPath(): ?string
    {
        return $this->nestedPath;
    }

    public function isReversedNested(): bool
    {
        return $this->reversedNested;
    }

    private function setQuery($value): void
    {
        switch ($this->type) {
            case self::TYPE_TERM:
                $this->value = $value;
                $this->query = ['term' => [$this->getField() => ['value' => $value]]];
                break;
            case self::TYPE_TERMS:
                $this->value = \is_array($value) ? $value : [$value];
                $this->query = ['terms' => [$this->getField() => $value]];
                break;
            case self::TYPE_DATE_RANGE:
                $this->value = \is_array($value) ? $value : [$value];
                $this->query = $this->getQueryDateRange($this->value);
                break;
            case self::TYPE_DATE_VERSION:
                $this->value = $value;
                $this->query = $this->getQueryVersion();
                break;
        }
    }

    private function getQueryDateRange(array $value): ?array
    {
        if (!isset($value['start']) && !isset($value['end'])) {
            return null;
        }

        $start = $end = null;

        if (!empty($value['start'])) {
            $startDatetime = $this->createDateTimeForQuery($value['start'], ' 00:00:00');
            $start = $startDatetime ? $startDatetime->format('Y-m-d') : null;
        }
        if (!empty($value['end'])) {
            $endDatetime = $this->createDateTimeForQuery($value['end'], ' 23:59:59');
            $end = $endDatetime ? $endDatetime->format('Y-m-d') : null;
        }

        if (null === $start && null === $end) {
            return null;
        }

        return ['range' => [$this->getField() => \array_filter(['gte' => $start, 'lte' => $end])]];
    }

    /**
     * @return array<mixed>
     */
    private function getQueryVersion(): ?array
    {
        if (null === $this->value || !\is_string($this->value)) {
            return null;
        }

        if ('now' === $this->value) {
            $dateTime = new \DateTimeImmutable();
        } else {
            $format = \is_string($this->dateFormat) ? $this->dateFormat : \DATE_ATOM;
            $dateTime = \DateTimeImmutable::createFromFormat($format, $this->value);
        }

        if (!$dateTime instanceof \DateTimeImmutable) {
            return null;
        }

        $dateString = $dateTime->format('Y-m-d');

        $fromField = $this->field ?? 'version_from_date';
        $toField = $this->secondaryField ?? 'version_to_date';

        return [
            'bool' => [
                'must' => [
                    ['range' => [$fromField => ['lte' => $dateString, 'format' => 'yyyy-MM-dd']]],
                    $this->getQueryOptional($toField, [
                        'range' => [$toField => ['gt' => $dateString, 'format' => 'yyyy-MM-dd']],
                    ]),
                ],
            ],
        ];
    }

    private function createDateTimeForQuery(string $value, ?string $time = ''): ?\DateTime
    {
        if (false === $this->dateFormat) {
            return new \DateTime($value);
        }

        if (!\is_string($this->dateFormat)) {
            return null;
        }

        $dateTime = \DateTime::createFromFormat($this->dateFormat, \sprintf('%s %s', $value, $time));

        return $dateTime instanceof \DateTime ? $dateTime : null;
    }

    /**
     * @param array<mixed>|null $query
     *
     * @return array<mixed>
     */
    private function getQueryOptional(string $field, ?array $query): array
    {
        return [
            'bool' => [
                'minimum_should_match' => 1,
                'should' => [
                    [$query],
                    ['bool' => ['must_not' => ['exists' => ['field' => $field]]]],
                ],
            ],
        ];
    }

    private function setChoices(): void
    {
        if (null != $this->choices || self::TYPE_TERMS !== $this->type) {
            return;
        }

        $aggs = ['terms' => ['field' => $this->getField(), 'size' => $this->aggSize]];
        if (null !== $this->getSortField()) {
            $aggs['terms']['order'] = [$this->getSortField() => $this->getSortOrder()];
        }

        if ($this->isNested()) {
            $aggs = ['nested' => [
                'path' => $this->getNestedPath(), ],
                'aggs' => ['nested' => $aggs],
            ];
        }

        $search = $this->clientRequest->searchArgs(['type' => $this->queryTypes, 'body' => ['query' => $this->queryFilters, 'size' => 0, 'aggs' => [$this->name => $aggs]]]);

        $result = $search['aggregations'][$this->name];
        $buckets = $this->isNested() ? $result['nested']['buckets'] : $result['buckets'];
        $choices = [];

        foreach ($buckets as $bucket) {
            $choices[$bucket['key']] = [
                'total' => $bucket['doc_count'],
                'filter' => 0,
                'active' => \in_array($bucket['key'], \is_array($this->value) ? $this->value : []),
            ];
        }

        $this->choices = $choices;
    }

    private function setPostFilter(array $options)
    {
        if (isset($options['post_filter'])) {
            $this->postFilter = (bool) $options['post_filter'];
        } elseif (self::TYPE_TERMS === $this->type && $this->public) {
            $this->postFilter = true; //default post filtering for public terms filters
        } else {
            $this->postFilter = false;
        }
    }
}
