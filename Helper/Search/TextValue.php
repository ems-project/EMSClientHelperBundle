<?php

namespace EMS\ClientHelperBundle\Helper\Search;

/**
 * If we search for 'foo bar'
 * the SearchManager will create two SearchValue instances
 */
class TextValue
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

    public function addSynonym(string $field, array $doc): void
    {
        $this->synonyms[] = [
            'match' => [
                $field => [
                    'query' => sprintf('%s:%s', $doc['_source']['_contenttype'], $doc['_id']),
                    'operator' => 'AND',
                ]
            ]
        ];
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function makeShould($searchFields, string $analyzer, float $boost = 1.0): array
    {
        $should = [];
        $should[] = $this->getQuery($searchFields, $analyzer, $boost);

        foreach ($this->synonyms as $synonym) {
            $should[] = $synonym;
        }

        return [
            'bool' => [
                'should' => $should,
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
