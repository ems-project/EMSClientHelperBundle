<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use Symfony\Component\HttpFoundation\Request;

class Filter
{
    /** @var string */
    private $name;
    /** @var string */
    private $type;
    /** @var string */
    private $field;
    /** @var null|int */
    private $aggSize;
    /** @var mixed */
    private $requestValue;

    /** @var array */
    private $query = [];

    const TYPE_TERMS      = 'terms';
    const TYPE_DATE_RANGE = 'date_range';

    const TYPES = [
        self::TYPE_TERMS      => 'Terms',
        self::TYPE_DATE_RANGE => 'DateRange',
    ];

    public function __construct(string $name, array $options)
    {
        if (!\array_key_exists($options['type'], self::TYPES)) {
            throw new \Exception(sprintf('invalid filter type %s', $options['type']));
        }

        $this->name = $name;
        $this->type = $options['type'];
        $this->field = $options['field'];
        $this->aggSize = isset($options['aggs_size']) ? (int) $options['aggs_size'] : null;
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

    public function getRequestValue()
    {
        return $this->requestValue;
    }

    public function hasAggSize(): bool
    {
        return $this->aggSize !== null;
    }

    public function getAggSize(): ?int
    {
        return $this->aggSize;
    }

    public function hasQuery(): bool
    {
        return !empty($this->query);
    }

    public function getQuery(): ?array
    {
        return $this->query;
    }

    public function handleRequest(Request $request): void
    {
        if (null == $value = $request->get($this->name)) {
            return;
        }

        $this->requestValue = $value;

        switch ($this->type) {
            case self::TYPE_TERMS:
                $this->setQueryTerms($value);
                break;
            case self::TYPE_DATE_RANGE:
                $this->setQueryDateRange($value);
                break;
        }
    }

    private function setQueryTerms($value): void
    {
        $this->query = [
            'terms' => [
                $this->field => (\is_array($value) ? $value : [$value])
            ]
        ];
    }

    private function setQueryDateRange($value): void
    {
        if (!\is_array($value)) {
            return;
        }

        $start = isset($value['start']) ? \DateTime::createFromFormat('d-m-Y H:i:s', $value['start'].' 00:00:00') : false;
        $end = isset($value['end']) ? \DateTime::createFromFormat('d-m-Y H:i:s', $value['end'].' 23:59:59') : false;

        if (!$start && !$end) {
            return;
        }

        $this->query = ['range' => [
            $this->field => array_filter([
                'gte' => $start ? $start->format('Y-m-d') : null,
                'lte' => $end ? $end->format('Y-m-d') : null,
            ])
        ]];
    }
}
