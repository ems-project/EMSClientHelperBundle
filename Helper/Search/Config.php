<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use Symfony\Component\HttpFoundation\Request;

class Config
{
    /** @var array */
    private $types;
    /** @var array [facet_name => size], used for aggregation */
    private $facets;
    /** @var array */
    private $synonyms;
    /** @var array */
    private $fields;

    /** @var string|null free text search */
    private $queryString;
    /** @var array */
    private $filterFacets = [];

    /** @var int */
    private $page = 0;
    /** @var int */
    private $limit = 1000;
    /** @var string|null */
    private $sortBy;
    /** @var string */
    private $sortOrder = 'asc';

    private function __construct()
    {
    }

    public static function fromClientRequest(ClientRequest $clientRequest): Config
    {
        if (!$clientRequest->hasOption('search')) {
            throw new \LogicException('no search defined!');
        }

        $locale = $clientRequest->getLocale();

        return self::create($clientRequest->getOption('[search]'), $locale);
    }

    public function bindRequest(Request $request): void
    {
        $this->queryString = $request->get('q', $this->queryString);
        $this->filterFacets = $request->get('f', $this->filterFacets);
        ;

        $this->page = (int) $request->get('p', $this->page);
        $this->limit = (int) $request->get('l', $this->limit);
        $this->sortBy = $request->get('s', $this->sortBy);
        $this->sortOrder = $request->get('o', $this->sortOrder);
    }

    private static function create(array $options, string $locale): Config
    {
        $config = new self;
        $config->types = $options['types']; //required
        $config->facets = $options['facets'] ?? [];
        $config->synonyms = $options['synonyms'] ?? [];
        $config->limit = $options['default_limit'] ?? $config->limit;

        $config->setFields(($options['fields'] ?? []), $locale);

        return $config;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getFacetsAggs(): array
    {
        $aggs = [];

        foreach ($this->facets as $facet => $size) {
            $aggs[$facet] = ['terms' => ['field' => $facet, 'size' => $size]];
        }

        return [];
    }

    public function getSynonyms(): array
    {
        return $this->synonyms;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    private function setFields(array $fields, string $locale): void
    {
        $this->fields = array_map(function (string $field) use ($locale) {
            return str_replace('%locale%', $locale, $field);
        }, $fields);
    }

    public function getSuggestions(): array
    {
        $suggestions = ['text' => ($this->queryString ?: false)];

        foreach ($this->fields as $field) {
            $suggestions['suggest-' . $field] = ['term' => ['field' => $field]];
        }

        return $suggestions;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function getFilterFacets(): array
    {
        return $this->filterFacets;
    }

    public function createFilter(): array
    {
        $filter = [];

        foreach ($this->filterFacets as $field => $terms) {
            if (empty($terms) || !array_key_exists($field, $this->facets)) {
                continue;
            }

            $filter['bool']['must'][] = ['terms' => [$field => $terms]];
        }

        return $filter;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getFrom(): int
    {
        return $this->page * $this->limit;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getSort(): ?array
    {
        if ($this->sortBy) {
            return [$this->sortBy => ['order' => $this->sortOrder, 'missing' => '_last', 'unmapped_type' => 'long']];
        }

        return null;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }
}
