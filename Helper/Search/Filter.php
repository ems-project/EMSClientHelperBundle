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

    /** @var ?string */
    private $sortField;
    /** @var string */
    private $sortOrder;

    /** @var null|int */
    private $aggSize;
    /** @var bool default true for terms, when value passed default false */
    private $postFilter;
    /** @var bool only public filters will handle a request. */
    private $public;
    /** @var bool if not all doc contain the filter */
    private $optional;

    /** @var array */
    private $query = [];

    /** @var array|null */
    private $value;
    /** @var array */
    private $choices = [];

    const TYPE_TERM       = 'term';
    const TYPE_TERMS      = 'terms';
    const TYPE_DATE_RANGE = 'date_range';

    const TYPES = [
        self::TYPE_TERM,
        self::TYPE_TERMS,
        self::TYPE_DATE_RANGE,
    ];

    public function __construct(ClientRequest $clientRequest, string $name, array $options)
    {
        $this->clientRequest = $clientRequest;

        if (!\in_array($options['type'], self::TYPES)) {
            throw new \Exception(sprintf('invalid filter type %s', $options['type']));
        }

        $this->name = $name;
        $this->type = $options['type'];
        $this->field = $options['field'];
        $this->public = isset($options['public']) ? (bool) $options['public'] : true;
        $this->optional = isset($options['optional']) ? (bool) $options['optional'] : false;
        $this->aggSize = isset($options['aggs_size']) ? (int) $options['aggs_size'] : null;
        $this->sortField = isset($options['sort_field']) ? $options['sort_field'] : null;
        $this->sortOrder = isset($options['sort_order']) ? $options['sort_order'] : 'asc';
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
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function hasAggSize(): bool
    {
        return $this->aggSize !== null;
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
        if ($this->optional) {
            return $this->getQueryOptional();
        }

        return $this->query;
    }

    public function isPostFilter(): bool
    {
        return $this->postFilter;
    }

    public function handleRequest(Request $request): void
    {
        $this->field = str_replace('%locale%', $request->getLocale(), $this->field);
        $requestValue = $request->get($this->name);

        if ($this->value !== null) {
            $this->setQuery($this->value);
        } elseif ($this->public && $requestValue) {
            $this->setQuery($requestValue);
        }
    }

    public function handleAggregation(array $aggregation)
    {
        $this->setChoices();

        if (isset($aggregation['buckets'])) {
            $this->handleBuckets($aggregation['buckets']);
        } elseif (isset($aggregation['filtered_'.$this->name]['buckets'])) {
            $this->handleBuckets($aggregation['filtered_'.$this->name]['buckets']);
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

    private function setQuery($value): void
    {
        switch ($this->type) {
            case self::TYPE_TERM:
                $this->value = $value;
                $this->query = ['term' => [$this->field => ['value' => $value]]];
                break;
            case self::TYPE_TERMS:
                $this->value = \is_array($value) ? $value : [$value];
                $this->query = ['terms' => [$this->field => $value]];
                break;
            case self::TYPE_DATE_RANGE:
                $this->value = \is_array($value) ? $value : [$value];
                $this->query = $this->getQueryDateRange($this->value);
                break;
        }
    }

    private function getQueryDateRange(array $value): ?array
    {
        if (!isset($value['start']) && !isset($value['end'])) {
            return null;
        }

        $format = 'd-m-Y H:i:s';
        $start = $end = null;

        if (!empty($value['start'])) {
            $startDatetime = \DateTime::createFromFormat($format, $value['start'].' 00:00:00');
            $start = $startDatetime ? $startDatetime->format('Y-m-d') : $value['start'];
        }
        if (!empty($value['end'])) {
            $endDatetime = \DateTime::createFromFormat($format, $value['end'].' 23:59:59');
            $end = $endDatetime ? $endDatetime->format('Y-m-d') : $value['end'];
        }

        if ($start === null && $end === null) {
            return null;
        }

        return ['range' => [ $this->field => array_filter(['gte' => $start, 'lte' => $end,]) ]];
    }

    private function getQueryOptional(): array
    {
        return [
            'bool' => [
                'should' => [
                    [$this->query],
                    ['bool' => [
                        'must_not' => ['exists' => ['field' => $this->field]]
                    ]]
                ]
            ]
        ];
    }

    private function setChoices(): void
    {
        if (null != $this->choices ||$this->type !== self::TYPE_TERMS) {
            return;
        }

        $aggs = ['field' => $this->field, 'size' => $this->aggSize];
        if ($this->getSortField() !== null) {
            $aggs['order'] = [$this->getSortField() => $this->getSortOrder()];
        }

        $search = $this->clientRequest->searchArgs([
            'body' => [
                'size' => 0,
                'aggs' => [$this->name => ['terms' =>  $aggs]]
            ]
        ]);
        $buckets = $search['aggregations'][$this->name]['buckets'];
        $choices = [];

        foreach ($buckets as $bucket) {
            $choices[$bucket['key']] = [
                'total' => $bucket['doc_count'],
                'filter' => 0,
                'active' => \in_array($bucket['key'], $this->value ?? [])
            ];
        }

        $this->choices = $choices;
    }

    private function setPostFilter(array $options)
    {
        if (isset($options['post_filter'])) {
            $this->postFilter = (bool) $options['post_filter'];
        } else if ($this->type === self::TYPE_TERMS && $this->public) {
            $this->postFilter = true; //default post filtering for public terms filters
        } else {
            $this->postFilter = false;
        }
    }

    private function handleBuckets(array $buckets)
    {
        foreach ($buckets as $bucket) {
            if (isset($this->choices[$bucket['key']])) {
                $this->choices[$bucket['key']]['filter'] = $bucket['doc_count'];
            }
        }
    }
}
