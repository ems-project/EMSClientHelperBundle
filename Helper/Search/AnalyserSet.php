<?php

namespace EMS\ClientHelperBundle\Helper\Search;

class AnalyserSet
{
    /** @var string */
    private $field;
    /** @var array */
    private $filter;
    /** @var float */
    private $boost;

    public function __construct($field, $filter = [], $boost = 1.0)
    {
        $this->field = $field;
        $this->filter = $filter;
        $this->boost = $boost;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getBoost(): float
    {
        return $this->boost;
    }

    public function getFilter(): ?array
    {
        return $this->filter;
    }
}
