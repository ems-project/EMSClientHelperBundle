<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Search;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Elasticsearch\Response\Response;
use Symfony\Component\HttpFoundation\Request;

final class Manager
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

        $response = Response::fromResultSet($this->clientRequest->commonSearch($search));
        $requestSearch->bindAggregations($response, $qbService->getQueryFilters());

        return [
            'results' => $response,
            'search' => $requestSearch,
            'query' => $requestSearch->getQueryString(),
            'sort' => $requestSearch->getSortBy(),
            'facets' => $requestSearch->getQueryFacets(),
            'page' => $requestSearch->getPage(),
            'size' => $requestSearch->getSize(),
        ];
    }
}
