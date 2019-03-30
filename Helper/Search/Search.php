<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use Symfony\Component\HttpFoundation\Request;

class Search
{
    /** @var array */
    private $types;
    /** @var array [facet_name => size], used for aggregation */
    private $facets;
    /** @var Synonym[] */
    private $synonyms;
    /** @var array */
    private $fields;

    /** @var string|null free text search */
    private $queryString;
    /** @var array */
    private $queryFacets = [];

    /** @var int */
    private $page = 0;
    /** @var int */
    private $limit = 1000;
    /** @var string|null */
    private $sortBy;
    /** @var string */
    private $sortOrder = 'asc';

    public function __construct(ClientRequest $clientRequest)
    {
        $options = $this->getOptions($clientRequest);

        if (isset($options['facets'])) {
            @trigger_error('Deprecated facets, please use filters setting', E_USER_DEPRECATED);
        }

        $this->types = $options['types']; //required
        $this->facets = $options['facets'] ?? [];
        $this->limit = $options['default_limit'] ?? $this->limit;
        $this->setFields(($options['fields'] ?? []), $clientRequest->getLocale());
        $this->setSynonyms(($options['synonyms'] ?? []), $clientRequest->getLocale());
    }

    public function bindRequest(Request $request): void
    {
        $this->queryString = $request->get('q', $this->queryString);
        $this->queryFacets = $request->get('f', $this->queryFacets);

        $this->page = (int) $request->get('p', $this->page);
        $this->limit = (int) $request->get('l', $this->limit);
        $this->sortBy = $request->get('s', $this->sortBy);
        $this->sortOrder = $request->get('o', $this->sortOrder);
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

        return $aggs;
    }

    public function hasSynonyms(): bool
    {
        return null != $this->synonyms;
    }

    /**
     * @return Synonym[]
     */
    public function getSynonyms(): array
    {
        return $this->synonyms;
    }

    public function setSynonyms(array $synonyms, string $locale): void
    {
        foreach ($synonyms as $options) {
            if (\is_string($options)) {
                $options = ['types' => [$options]];
            }

            $this->synonyms[] = new Synonym($options, $locale);
        }
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

    public function hasQueryString(): bool
    {
        return null != $this->queryString;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function getQueryFacets(): array
    {
        return $this->queryFacets;
    }

    public function createFilter(): array
    {
        $filter = [];

        foreach ($this->queryFacets as $field => $terms) {
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

    private function getOptions(ClientRequest $clientRequest): array
    {
        if ($clientRequest->hasOption('search_config')) {
            return $clientRequest->getOption('[search_config]');
        }

        if ($clientRequest->hasOption('search')) {
            @trigger_error('Deprecated search option please use search_config!', E_USER_DEPRECATED);

            return $clientRequest->getOption('[search]');
        }

        throw new \LogicException('no search defined!');
    }
}
