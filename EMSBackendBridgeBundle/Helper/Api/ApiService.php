<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Helper\Api;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $name = $clientRequest->getOption('[api][name]', false);

        if ($name) {
            $this->clientRequests[$name] = $clientRequest;
        }
    }

    /**
     * @param string $apiName
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function getContentTypes($apiName)
    {
        $response = new Response();
        $contentTypes = $this->getClientRequest($apiName)->getContentTypes();

        foreach ($contentTypes as $contentType) {
            $url = $this->urlGenerator->generate('emsch_api_content_type', [
                'apiName' => $apiName,
                'contentType' => $contentType
            ]);

            $response->addData('content_types', [
                'name' => $contentType,
                '_links' => [
                    Response::createLink('self', $url, $contentType)
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
     * @return Response
     */
    public function getContentType($apiName, $contentType, array $filter = [], $size = null, $scrollId = null)
    {
        $response = new Response();

        $urlParent = $this->urlGenerator->generate('emsch_api_content_types', ['apiName' => $apiName]);
        $response->addData('_links', [Response::createLink('content-types', $urlParent, 'content types')]);

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
            $data['_links'] = [Response::createLink('self', $url, $contentType)];

            $response->addData('all', $data);
        }

        return $response;
    }

    /**
     * @param string $apiName
     * @param string $contentType
     * @param string $ouuid
     *
     * @return Response
     */
    public function getDocument($apiName, $contentType, $ouuid)
    {
        $urlParent = $this->urlGenerator->generate('emsch_api_content_type', [
            'apiName' => $apiName,
            'contentType' => $contentType
        ]);

        $document = $this->getClientRequest($apiName)->get($contentType, $ouuid);

        $response = new Response();
        $response->addData('_links', [Response::createLink('all', $urlParent, $contentType)]);
        $response->addData($contentType, array_merge_recursive(['id' => $document['_id']], $document['_source']));

        return $response;
    }

    /**
     * @param string $apiName
     *
     * @return ClientRequest
     *
     * @throws NotFoundHttpException
     */
    private function getClientRequest($apiName)
    {
        if (!isset($this->clientRequests[$apiName])) {
            throw new NotFoundHttpException();
        }

        return $this->clientRequests[$apiName];
    }
}