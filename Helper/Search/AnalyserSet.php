<?php

namespace EMS\ClientHelperBundle\Helper\Search;

class AnalyserSet
{
    /** @var string */
    private $field;
    /** @var array */
    private $filter;
    /** @var string */
    private $synonymsSearchField;
    /** @var string|array */
    private $synonymsFilter;
    /** @var string|array */
    private $synonymTypes;
    /** @var string */
    private $searchSynonymsInField;
    /** @var float */
    private $boost;

    public function __construct($field, $filter = [], $synonymTypes = [], $synonymsSearchField = false, $searchSynonymsInField = false, $synonymsFilter = '', $boost = 1.0)
    {
        $this->field = $field;
        $this->filter = $filter;
        $this->synonymTypes = $synonymTypes;
        $this->synonymsSearchField = $synonymsSearchField;
        $this->searchSynonymsInField = $searchSynonymsInField;
        $this->synonymsFilter = $synonymsFilter;
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

    public function getSearchSynonymsInField(): string
    {
        return $this->searchSynonymsInField;
    }

    public function getSynonymsSearchField(): string
    {
        return $this->synonymsSearchField;
    }

    public function getSynonymTypes()
    {
        return $this->synonymTypes;
    }

    public function getSynonymsFilter(): ?array
    {
        if (is_string($this->synonymsFilter)) {
            return json_decode($this->synonymsFilter, true);
        }

        return $this->synonymsFilter;
    }
}
