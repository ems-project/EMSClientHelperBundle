<?php

namespace EMS\ClientHelperBundle\Helper\Search;

class Synonym
{
    /** @var array */
    private $types;
    /** @var null|string */
    private $field;
    /** @var null|string */
    private $searchField;
    /** @var array */
    private $filter;

    public function __construct(array $data, string $locale)
    {
        $this->types = $data['types'] ?? [];
        $this->filter = $data['filter'] ?? [];

        if (isset($data['field'])) {
            $this->field = str_replace('%locale%', $locale, $data['field']);
        }

        if (isset($data['search'])) {
            $this->searchField = str_replace('%locale%', $locale, $data['search']);
        }
    }

    public function getSearchField(): string
    {
        return $this->searchField ?? '_all';
    }

    public function getField(): ?string
    {
        return $this->field ?? '_all';
    }

    public function getQuery(array $queryTextValue): array
    {
        $query = [
            'bool' => [
                'must' => [
                    $queryTextValue
                ]
            ]
        ];

        if ($this->types) {
            $query['bool']['must'][] = ['terms' => ['_contenttype' => $this->types]];
        }


        if ($this->filter) {
            $query['bool']['must'][] = $this->filter;
        }

        return $query;
    }
}