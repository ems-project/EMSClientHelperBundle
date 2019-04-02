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

    const TYPE_TERMS      = 'terms';
    const TYPE_DATE_RANGE = 'date_range';

    const TYPES = [
        self::TYPE_TERMS      => 'Terms',
        self::TYPE_DATE_RANGE => 'DateRange',
    ];

    public function __construct(ClientRequest $clientRequest, string $name, array $options)
    {
        $this->clientRequest = $clientRequest;

        if (!\array_key_exists($options['type'], self::TYPES)) {
            throw new \Exception(sprintf('invalid filter type %s', $options['type']));
        }

        $this->name = $name;
        $this->type = $options['type'];
        $this->field = $options['field'];
        $this->public = isset($options['public']) ? (bool) $options['public'] : true;
        $this->optional = isset($options['optional']) ? (bool) $options['optional'] : true;
        $this->aggSize = isset($options['aggs_size']) ? (int) $options['aggs_size'] : null;
        $this->setPostFilter($options);

        if (isset($options['value'])) {
            $this->setQuery($options['value']);
        }
    }

    public function getName(): string
    {
        return $this->name;
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
        $value = $request->get($this->name);

        if (!$this->public || null == $value) {
            return;
        }

        $this->setQuery($value);
    }

    public function handleAggregation(array $aggregation)
    {
        $this->setChoices();

        foreach ($aggregation['buckets'] as $bucket) {
            if (isset($this->choices[$bucket['key']])) {
                $this->choices[$bucket['key']]['filter'] = $bucket['doc_count'];
            }
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
        $this->value = (\is_array($value) ? $value : [$value]);

        switch ($this->type) {
            case self::TYPE_TERMS:
                $this->query = $this->getQueryTerms($this->value);
                break;
            case self::TYPE_DATE_RANGE:
                $this->query = $this->getQueryDateRange($this->value);
                break;
        }
    }

    private function getQueryTerms(array $value): array
    {
        return ['terms' => [$this->field => $value]];
    }

    private function getQueryDateRange(array $value): ?array
    {
        if (!isset($value['start']) && !isset($value['end'])) {
            return null;
        }

        $format = 'd-m-Y H:i:s';

        if (isset($value['start'])) {
            $start = \DateTime::createFromFormat($format, $value['start'].' 00:00:00');
        }
        if (isset($value['end'])) {
            $end = \DateTime::createFromFormat($format, $value['end'].' 23:59:59');
        }

        return ['range' => [
            $this->field => array_filter([
                'gte' => isset($start) ? $start->format('Y-m-d') : null,
                'lte' => isset($end) ? $end->format('Y-m-d') : null,
            ])
        ]];
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

        $search = $this->clientRequest->searchArgs([
            'body' => [
                'size' => 0,
                'aggs' => [$this->name => ['terms' => ['field' => $this->field, 'size' => $this->aggSize] ]]
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
}
