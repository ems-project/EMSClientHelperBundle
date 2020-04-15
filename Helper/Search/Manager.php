<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Search\Filter\Filter;
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

        $qb = new QueryBuilder($this->clientRequest, $search);
        $this->searchChoices($search, $qb);

        $body = $qb->buildBody();
        $results = $this->clientRequest->search($search->getTypes(), $body, $search->getFrom(), $search->getSize());

        if (isset($results['aggregations'])) {
            $search->bindAggregations($results['aggregations']);
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

    private function searchChoices(Search $search, QueryBuilder $qb): void
    {
        foreach ($search->getFilters() as $filter) {
            if ($filter->getType() !== Filter::TYPE_TERMS) {
                continue;
            }

            $result = $this->clientRequest->searchArgs([
                'type' => $search->getTypes(),
                'body' =>  $qb->buildBodyForFilterChoices($filter),
                'size' => 0
            ]);

            if (isset($result['aggregations'][$filter->getName()])) {
                $filter->setChoices($result['aggregations'][$filter->getName()]);
            }
        }
    }
}
