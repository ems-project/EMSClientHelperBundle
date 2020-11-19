<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Search;

/**
 * If we search for 'foo bar'
 * the SearchManager will create two SearchValue instances.
 */
class TextValue
{
    /** @var string */
    private $text;
    /** @var string */
    private $field;
    /** @var string */
    private $analyzer;

    /** @var array */
    private $synonyms;

    public function __construct(string $text, string $field, string $analyzer)
    {
        $this->text = $text;
        $this->field = $field;
        $this->analyzer = $analyzer;
        $this->synonyms = [];
    }

    public function getAnalyzer(): string
    {
        return $this->analyzer;
    }

    public function addSynonym(string $synonymField, array $doc): void
    {
        $this->synonyms[] = [
            'match' => [
                $synonymField => [
                    'query' => \sprintf('%s:%s', $doc['_source']['_contenttype'], $doc['_id']),
                    'operator' => 'AND',
                ],
            ],
        ];
    }

    public function makeShould(float $boost = 1.0): array
    {
        $should = [];
        $should[] = $this->getQuery($this->field, $this->analyzer, $boost);

        foreach ($this->synonyms as $synonym) {
            $should[] = $synonym;
        }

        return [
            'bool' => [
                'should' => $should,
            ],
        ];
    }

    public function getQuery(string $field, string $analyzer, float $boost = 1.0): array
    {
        $matches = [];
        \preg_match_all('/^\"(.*)\"$/', $this->text, $matches);

        if (isset($matches[1][0])) {
            return [
                'match_phrase' => [
                    ($field ? $field : '_all') => [
                        'analyzer' => $analyzer,
                        'query' => $matches[1][0],
                        'boost' => $boost,
                    ],
                ],
            ];
        }

        if (false !== \strpos($this->text, '*')) {
            return [
                'query_string' => [
                    'default_field' => $field ?: '_all',
                    'query' => $this->text,
                    'analyzer' => $analyzer,
                    'analyze_wildcard' => true,
                    'boost' => $boost,
                ],
            ];
        }

        return [
            'match' => [
                ($field ? $field : '_all') => [
                    'query' => $this->text,
                    'boost' => $boost,
                ],
            ],
        ];
    }
}
