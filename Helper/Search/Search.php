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
    /** @var Filter[] */
    private $filters = [];

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

        $filters = $options['filters'] ?? [];
        foreach ($filters as $name => $options) {
            $this->filters[$name] = new Filter($clientRequest, $name, $options);
        }
    }

    public function bindRequest(Request $request): void
    {
        $this->queryString = $request->get('q', $this->queryString);
        $this->queryFacets = $request->get('f', $this->queryFacets);

        $this->page = (int) $request->get('p', $this->page);
        $this->limit = (int) $request->get('l', $this->limit);
        $this->sortBy = $request->get('s', $this->sortBy);
        $this->sortOrder = $request->get('o', $this->sortOrder);

        foreach ($this->filters as $filter) {
            $filter->handleRequest($request);
        }
    }

    public function bindAggregations(array $aggregations): void
    {
        foreach ($aggregations as $name => $aggregation) {
            if ($this->hasFilter($name)) {
                $this->getFilter($name)->handleAggregation($aggregation);
            }
        }
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return Synonym[]
     */
    public function getSynonyms(): array
    {
        return $this->synonyms;
    }

    public function getFields(): array
    {
        return $this->fields;
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
        $queryFacets = [];

        foreach ($this->queryFacets as $field => $terms) {
            if (array_key_exists($field, $this->facets) && !empty($terms)) {
                $queryFacets[$field] = $terms;
            }
        }

        return $queryFacets;
    }

    public function hasFilter(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    public function getFilter(string $name): Filter
    {
        return $this->filters[$name];
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return Filter[]
     */
    public function getActiveFilters()
    {
        return \array_filter($this->filters, function (Filter $filter) {
            return $filter->isActive() && $filter->isPublic();
        });
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

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function getSortOrder(): string
    {
        return $this->sortOrder;
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

    private function setFields(array $fields, string $locale): void
    {
        $this->fields = array_map(function (string $field) use ($locale) {
            return str_replace('%locale%', $locale, $field);
        }, $fields);
    }

    private function setSynonyms(array $synonyms, string $locale): void
    {
        foreach ($synonyms as $options) {
            if (\is_string($options)) {
                $options = ['types' => [$options]];
            }

            $this->synonyms[] = new Synonym($options, $locale);
        }
    }
}
