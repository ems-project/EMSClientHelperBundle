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
        $search = new Search($this->clientRequest);
        $search->bindRequest($request);

        $synonyms = $search->getSynonyms();
        $filter = $search->createFilter();

        $analyzers = [];
        foreach ($search->getFields() as $field) {
            $analyzers[] = new AnalyserSet($field, $filter, $synonyms, empty($synonyms) ? false : ($field));
        }

        $qbService = new QueryBuilder($this->clientRequest);
        $query = $qbService->getQuery($search->getQueryString(), $analyzers);

        $body = array_filter([
            'query' => $query,
            'aggs' => $search->getFacetsAggs(),
            'suggest' => $search->getSuggestions(),
            'sort' => $search->getSort(),
        ]);

        $results = $this->clientRequest->search($search->getTypes(), $body, $search->getFrom(), $search->getLimit());

        return [
            'results' => $results,
            'query' => $search->getQueryString(),
            'sort' => $search->getSortBy(),
            'facets' => $search->getQueryFacets(),
            'page' => $search->getPage(),
            'size' => $search->getLimit(),
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
