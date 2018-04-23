<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\ApiNotFoundException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Model\ApiResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiService
{
    /**
     * @var ClientRequest[]
     */
    private $clientRequests;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
        $this->clientRequests = [];
    }

    /**
     * @param ClientRequest $clientRequest
     */
    public function addClientRequest(ClientRequest $clientRequest)
    {
        if (true === $clientRequest->getOption('[api][enabled]')) {
            $name = $clientRequest->getOption('[api][name]');

            $this->clientRequests[$name] = $clientRequest;
        }
    }

    /**
     * @param string $apiName
     *
     * @return ApiResponse
     *
     * @throws ApiNotFoundException
     */
    public function getContentTypes($apiName)
    {
        $response = new ApiResponse();
        $contentTypes = $this->getClientRequest($apiName)->getContentTypes();

        foreach ($contentTypes as $contentType) {
            $url = $this->urlGenerator->generate('emsch_api_content_type', [
                'apiName' => $apiName,
                'contentType' => $contentType
            ]);

            $response->addData('content_types', [
                'name' => $contentType,
                '_links' => [
                    ApiResponse::createLink('self', $url, $contentType)
                ]
            ]);
        }

        return $response;
    }

    /**
     * @param string $apiName
     * @param string $contentType
     * @param array  $filter
     * @param string $size
     * @param string $scrollId
     *
     * @return ApiResponse
     */
    public function getContentType($apiName, $contentType, array $filter = [], $size = null, $scrollId = null)
    {
        $response = new ApiResponse();

        $urlParent = $this->urlGenerator->generate('emsch_api_content_types', ['apiName' => $apiName]);
        $response->addData('_links', [ApiResponse::createLink('content-types', $urlParent, 'content types')]);

        $results = $this->getClientRequest($apiName)->scroll($contentType, $filter, $size, $scrollId);

        $hits = $results['hits'];

        $response->addData('count', count($hits['hits']));
        $response->addData('total', $hits['total']);
        $response->addData('scroll', $results['_scroll_id']);

        foreach ($hits['hits'] as $document) {
            $url =  $this->urlGenerator->generate('emsch_api_document', [
                'apiName' => $apiName,
                'contentType' => $contentType,
                'ouuid' => $document['_id'],
            ]);

            $data = array_merge_recursive(['id' => $document['_id']], $document['_source']);
            $data['_links'] = [ApiResponse::createLink('self', $url, $contentType)];

            $response->addData('all', $data);
        }

        return $response;
    }

    /**
     * @param string $apiName
     * @param string $contentType
     * @param string $ouuid
     *
     * @return ApiResponse
     */
    public function getDocument($apiName, $contentType, $ouuid)
    {
        $urlParent = $this->urlGenerator->generate('emsch_api_content_type', [
            'apiName' => $apiName,
            'contentType' => $contentType
        ]);

        $document = $this->getClientRequest($apiName)->get($contentType, $ouuid);

        $response = new ApiResponse();
        $response->addData('_links', [ApiResponse::createLink('all', $urlParent, $contentType)]);
        $response->addData($contentType, array_merge_recursive(['id' => $document['_id']], $document['_source']));

        return $response;
    }

    /**
     * @param string $apiName
     *
     * @return ClientRequest
     *
     * @throws ApiNotFoundException
     */
    private function getClientRequest($apiName)
    {
        if (!isset($this->clientRequests[$apiName])) {
            throw new ApiNotFoundException();
        }

        return $this->clientRequests[$apiName];
    }
}