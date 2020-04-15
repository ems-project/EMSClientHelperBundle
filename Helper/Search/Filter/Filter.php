<?php

namespace EMS\ClientHelperBundle\Helper\Search\Filter;

use Symfony\Component\HttpFoundation\Request;

class Filter
{
    /** @var string */
    private $name;
    /** @var Options */
    private $options;
    /** @var string */
    private $type;
    /** @var string */
    private $field;
    /** @var array */
    private $query = [];
    /** @var array|null */
    private $value;
    /** @var array */
    private $choices = [];

    public const TYPE_TERM       = 'term';
    public const TYPE_TERMS      = 'terms';
    public const TYPE_DATE_RANGE = 'date_range';

    private const TYPES = [
        self::TYPE_TERM,
        self::TYPE_TERMS,
        self::TYPE_DATE_RANGE,
    ];

    public function __construct(string $name, Options $options)
    {
        if (!\in_array($options->getType(), self::TYPES)) {
            throw new \Exception(sprintf('invalid filter type %s', $options->getType()));
        }

        $this->name = $name;
        $this->type = $options->getType();
        $this->field = $options->getField();
        $this->options = $options;

        if ($options->hasValue()) {
            $this->setQuery($options->getValue());
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getField(): string
    {
        if ($this->getOptions()->hasNestedPath()) {
            return sprintf('%s.%s', $this->getOptions()->getNestedPath(), $this->field);
        }

        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return !empty($this->query);
    }

    /**
     * Keep used in twig templates
     */
    public function isPublic(): bool
    {
        return $this->getOptions()->isPublic();
    }

    public function getQuery(): ?array
    {
        if ($this->getOptions()->isOptional()) {
            return $this->getQueryOptional();
        }

        return $this->query;
    }

    public function isPostFilter(): bool
    {
        if ($this->getOptions()->hasPostFilter()) {
            return $this->getOptions()->getPostFilter();
        }

        if ($this->getOptions()->isType(self::TYPE_TERMS) && $this->getOptions()->isPublic()) {
            return true; //default post filtering for public terms filters
        }

        return false;
    }

    public function handleRequest(Request $request): void
    {
        $this->field = str_replace('%locale%', $request->getLocale(), $this->field);
        $requestValue = $request->get($this->name);

        if ($this->value !== null) {
            $this->setQuery($this->value);
        } elseif ($this->getOptions()->isPublic() && $requestValue) {
            $this->setQuery($requestValue);
        }
    }

    public function handleAggregation(array $aggregation)
    {
        $data = $aggregation['nested'] ?? $aggregation;
        $buckets = $data['filtered_' . $this->name]['buckets'] ?? $data['buckets'];

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
        return $this->choices;
    }

    public function setChoices(array $aggregation): void
    {
        if (!$this->getOptions()->isType(self::TYPE_TERMS)) {
            return;
        }

        $data = $aggregation['nested'] ?? $aggregation;
        $buckets = $data['filtered_' . $this->name]['buckets'] ?? $data['buckets'];

        foreach ($buckets as $bucket) {
            $this->choices[$bucket['key']] = [
                'total' => $bucket['doc_count'],
                'filter' => 0,
                'active' => \in_array($bucket['key'], $this->value ?? [])
            ];
        }
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
            $startDatetime = \DateTime::createFromFormat($format, $value['start'] . ' 00:00:00');
            $start = $startDatetime ? $startDatetime->format('Y-m-d') : $value['start'];
        }
        if (!empty($value['end'])) {
            $endDatetime = \DateTime::createFromFormat($format, $value['end'] . ' 23:59:59');
            $end = $endDatetime ? $endDatetime->format('Y-m-d') : $value['end'];
        }

        if ($start === null && $end === null) {
            return null;
        }

        return ['range' => [ $this->getField() => array_filter(['gte' => $start, 'lte' => $end,]) ]];
    }

    private function getQueryOptional(): array
    {
        return [
            'bool' => [
                'minimum_should_match' => 1,
                'should' => [
                    [$this->query],
                    ['bool' => [
                        'must_not' => ['exists' => ['field' => $this->getField()]]
                    ]]
                ]
            ]
        ];
    }
}
