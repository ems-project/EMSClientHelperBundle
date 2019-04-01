<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;

class Analyzer
{
    /** @var ClientRequest */
    private $clientRequest;

    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    /**
     * @param string $field
     * @param string $queryString
     * @param array  $synonyms
     *
     * @return TextValue[]
     */
    public function getTextValues(string $field, string $queryString, array $synonyms = []): array
    {
        $analyzer = $this->clientRequest->getFieldAnalyzer($field);
        $tokens = $this->clientRequest->analyze($queryString, $field);

        $textValues = [];

        foreach ($tokens as $token) {
            $textValue = new TextValue($token, $field, $analyzer);

            $this->addSynonyms($textValue, $synonyms);

            $textValues[$token] = $textValue;
        }

        return $textValues;
    }

    /**
     * @param TextValue $textValue
     * @param Synonym[] $synonyms
     */
    private function addSynonyms(TextValue $textValue, array $synonyms = []): void
    {
        foreach ($synonyms as $synonym) {
            $queryText = $textValue->getQuery($synonym->getSearchField(), $textValue->getAnalyzer());
            $querySynonym = $synonym->getQuery($queryText);

            $body = ['_source' => ['_contenttype'], 'query' => $querySynonym];
            $documents = $this->clientRequest->search([], $body, 0, 20);

            if ($documents['hits']['total'] > 20) {
                continue;
            }

            foreach ($documents['hits']['hits'] as $doc) {
                $textValue->addSynonym($synonym->getField(), $doc);
            }
        }
    }
}
