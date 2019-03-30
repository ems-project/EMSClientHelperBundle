<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Component\HttpFoundation\Request;

class Manager
{
    /** @var ClientRequest */
    private $clientRequest;

    public function __construct(ClientRequestManager $clientRequestManager)
    {
        $this->clientRequest = $clientRequestManager->getDefault();
    }

    public function getClientRequest(): ClientRequest
    {
        return $this->clientRequest;
    }

    public function search(Request $request)
    {
        $config = Search::fromClientRequest($this->clientRequest);
        $config->bindRequest($request);

        $synonyms = $config->getSynonyms();
        $filter = $config->createFilter();

        $analyzers = [];
        foreach ($config->getFields() as $field) {
            $analyzers[] = new AnalyserSet($field, $filter, $synonyms, empty($synonyms) ? false : ($field));
        }

        $qbService = new QueryBuilder($this->clientRequest);
        $query = $qbService->getQuery($config->getQueryString(), $analyzers);

        $body = array_filter([
            'query' => $query,
            'aggs' => $config->getFacetsAggs(),
            'suggest' => $config->getSuggestions(),
            'sort' => $config->getSort(),
        ]);

        $results = $this->clientRequest->search($config->getTypes(), $body, $config->getFrom(), $config->getLimit());

        return [
            'results' => $results,
            'query' => $config->getQueryString(),
            'sort' => $config->getSortBy(),
            'facets' => $config->getFilterFacets(),
            'page' => $config->getPage(),
            'size' => $config->getLimit(),
            'counters' => $this->getCountInfo($results),
        ];
    }

    private function getCountInfo(array $results): array
    {
        $counters = [];
        $aggregations = $results['aggregations'] ?? [];

        foreach ($aggregations as $type => $data) {
            $counters[$type] = [];

            foreach ($data['buckets'] as $bucket) {
                $counters[$type][$bucket['key']] = $bucket['doc_count'];
            }
        }

        return $counters;
    }
}
