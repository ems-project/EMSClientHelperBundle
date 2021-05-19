<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Search;

use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;

final class Synonym
{
    /** @var string[] */
    private $types;
    /** @var string|null */
    private $field;
    /** @var string|null */
    private $searchField;
    /** @var array<mixed> */
    private $filter;

    /**
     * @param array{types: ?string[], field: ?string, search: ?string, } $data
     */
    public function __construct(array $data, string $locale)
    {
        $this->types = $data['types'] ?? [];
        $this->filter = $data['filter'] ?? [];

        if (isset($data['field'])) {
            $this->field = \str_replace('%locale%', $locale, $data['field']);
        }

        if (isset($data['search'])) {
            $this->searchField = \str_replace('%locale%', $locale, $data['search']);
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

    public function getQuery(AbstractQuery $queryTextValue): AbstractQuery
    {
        $query = new BoolQuery();
        $query->addMust($queryTextValue);

        if (\count($this->types) > 0) {
            $terms = new Terms(EMSSource::FIELD_CONTENT_TYPE, $this->types);
            $query->addMust($terms);
        }

        if (\count($this->filter) > 0) {
            $query->addMust($this->filter);
        }

        return $query;
    }
}
