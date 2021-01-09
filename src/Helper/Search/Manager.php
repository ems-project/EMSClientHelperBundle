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

    /**
     * @return array<mixed>
     */
    public function search(Request $request): array
    {
        $requestSearch = new Search($this->clientRequest);
        $requestSearch->bindRequest($request);

        $qbService = new QueryBuilder($this->clientRequest, $requestSearch);
        $search = $qbService->buildSearch($requestSearch->getTypes());
        $search->setFrom($requestSearch->getFrom());
        $search->setSize($requestSearch->getSize());

        $results = $this->clientRequest->commonSearch($search)->getResponse()->getData();

        if (isset($results['aggregations'])) {
            $requestSearch->bindAggregations($results['aggregations'], $qbService->getQueryFilters());
        }

        return [
            'results' => $results,
            'search' => $requestSearch,
            'query' => $requestSearch->getQueryString(),
            'sort' => $requestSearch->getSortBy(),
            'facets' => $requestSearch->getQueryFacets(),
            'page' => $requestSearch->getPage(),
            'size' => $requestSearch->getSize(),
            'counters' => $this->getCountInfo($results),
        ];
    }

    /**
     * @param array<mixed> $results
     *
     * @return array<int|string, array>
     */
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
