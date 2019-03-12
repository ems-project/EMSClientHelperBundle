<?php

namespace EMS\ClientHelperBundle\Helper\Search;

/**
 * If we search for 'foo bar'
 * the SearchManager will create two SearchValue instances
 */
class SearchValue
{
    /** @var string */
    private $term;
    /** @var array */
    private $synonyms;

    public function __construct(string $term)
    {
        $this->term = $term;
        $this->synonyms = [];
    }

    public function addSynonym(array $document): void
    {
        $this->synonyms[] = sprintf('%s:%s', $document['_type'], $document['_id']);
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function makeShould($searchFields, string $synonymsSearchField, string $analyzerField, float $boost = 1.0): array
    {
        $should = [];
        $should[] = $this->getQuery($searchFields, $analyzerField, $boost);

        foreach ($this->synonyms as $emsLink) {
            if (!empty($emsLink)) {
                $should[] = $this->makeEmsLinkQuery($synonymsSearchField, $emsLink);
            }
        }

        return ['bool' => [
            'should' => $should,
        ]];
    }

    private function makeEmsLinkQuery(string $field, string $query): array
    {
        $searchField = ($field ? $field : '_all');

        return [
            'match' => [
                $searchField => [
                    'query' => $query,
                    'operator' => 'AND',
                ]
            ]
        ];
    }

    public function getQuery(string $field, string $analyzer, float $boost = 1.0): array
    {
        $matches = [];
        preg_match_all('/^\"(.*)\"$/', $this->term, $matches);

        if (isset($matches[1][0])) {
            return [
                'match_phrase' => [
                    ($field ? $field : '_all') => [
                        'analyzer' => $analyzer,
                        'query' => $matches[1][0],
                        'boost' => $boost
                    ]

                ]
            ];
        }

        if (strpos($this->term, '*') !== false) {
            return [
                'query_string' => [
                    'default_field' => $field ?: '_all',
                    'query' => $this->term,
                    'analyzer' => $analyzer,
                    'analyze_wildcard' => true,
                    'boost' => $boost
                ]
            ];
        }


        return [
            'match' => [
                ($field ? $field : '_all') => [
                    'query' => $this->getTerm(),
                    'boost' => $boost,
                ],
            ]
        ];
    }
}
