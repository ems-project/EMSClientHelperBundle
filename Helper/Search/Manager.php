<?php

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Entity\AnalyserSet;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Service\QueryBuilderService;
use Symfony\Component\HttpFoundation\Request;

class Manager
{
    /**
     * @var ClientRequestManager
     */
    private $clientRequestManager;

    public function __construct(ClientRequestManager $clientRequestManager)
    {
        $this->clientRequestManager = $clientRequestManager;
    }

    /**
     * @return ClientRequest
     */
    public function getClientRequest()
    {
        return $this->clientRequestManager->getDefault();
    }

    public function search(Request $request)
    {
        $clientRequest = $this->getClientRequest();

        $options = $clientRequest->getOption('[search]');
        $types = $options['types'] ?? [];
        $facets = $options['facets'] ?? [];
        $synonyms = $options['synonyms'] ?? [];
        $fields = $options['fields'] ?? [];
        $defaultLimit = $options['default_limit'] ?? 1000;

        $queryString = $request->get('q', false);
        $filterFacets = $request->get('f', []);
        $locale = $request->getLocale();
        $sortBy = $request->get('s', false);
        $sortOrder = $request->get('o','asc');
        $page = (int) $request->get('p', 0);
        $limit = (int) $request->get('l', $defaultLimit);

        $filter = '';
        if(!empty($filterFacets)) {
            $filter = [];
            foreach($filterFacets as $field => $terms) {
                if(!empty($terms)) {
                    $filter['bool']['must'][] = [
                        'terms' => [
                            $field => $terms,
                        ]
                    ];
                }
            }
        }

        $analyzers = [];

        foreach ($fields as $field) {
            $field = str_replace('%locale%', $locale, $field);
            $analyzers[] = new AnalyserSet($clientRequest, $field, $filter, $synonyms, empty($synonyms)?false:($field));
        }

        $qbService = new QueryBuilderService();
        $query = $qbService->getQuery($queryString, $analyzers);

        $aggs = [];
        if(!empty($facets)) {
            foreach ($facets as $facet => $size) {
                $aggs[$facet] = [
                    'terms' => [
                        'field' => $facet,
                        'size' => $size,
                ]];
            }
        }

        $body = [
            'query' => $query,
            'aggs' => $aggs,
            'suggest' => [
                'suggestion' => [
                    'text' => $queryString,
                    'term' => [
                        'field' => 'all_'.$locale,
                    ]
                ]
            ]
        ];

        if($sortBy) {
            $body['sort'] = [$sortBy => ['order' => $sortOrder, 'missing' => '_last', 'unmapped_type' => 'long']];
        }

        $results = $this->getClientRequest()->search($types, $body, $page*$limit, $limit);

        return [
            'results' => $results,
            'query' => $queryString,
            'sort' => $sortBy,
            'facets' => $filterFacets,
            'page' => $page,
            'size' => $limit,
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