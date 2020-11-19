<?php

declare(strict_types=1);

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

        $qbService = new QueryBuilder($this->clientRequest, $search);
        $body = $qbService->buildBody();

        $results = $this->clientRequest->search($search->getTypes(), $body, $search->getFrom(), $search->getSize());

        if (isset($results['aggregations'])) {
            $search->bindAggregations($results['aggregations'], $qbService->getQueryFilters());
        }

        return [
            'results' => $results,
            'search' => $search,
            'query' => $search->getQueryString(),
            'sort' => $search->getSortBy(),
            'facets' => $search->getQueryFacets(),
            'page' => $search->getPage(),
            'size' => $search->getSize(),
            'counters' => $this->getCountInfo($results),
        ];
    }

    private function getCountInfo(array $results): array
    {
        $counters = [];
        $aggregations = $results['aggregations'] ?? [];

        foreach ($aggregations as $type => $data) {
            $counters[$type] = [];
            $buckets = $data['buckets'] ?? [];

            foreach ($buckets as $bucket) {
                $counters[$type][$bucket['key']] = $bucket['doc_count'];
            }
        }

        return $counters;
    }
}
