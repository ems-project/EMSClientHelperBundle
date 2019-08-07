<?php

namespace EMS\ClientHelperBundle\Helper\Api;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\CommonBundle\Helper\EmsFields;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiService
{
    /**
     * @var ClientRequest[]
     */
    private $clientRequests;

    /**
     * @var Client[]
     */
    private $apiClients;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param iterable              $clientRequests
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, iterable $clientRequests = [], iterable $apiClients = [])
    {
        $this->urlGenerator = $urlGenerator;
        $this->clientRequests = $clientRequests;
        $this->apiClients = $apiClients;
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

    public function updateDocument(string $apiName, string $type, string $ouuid, array $body) : string
    {
        $response = $this->getApiClient($apiName)->updateDocument($type, $ouuid, $body);

        if (! $response['success']) {
            throw new \Exception(isset($response['error'][0])?$response['error'][0]:'Update document failed');
        }
        return $response['ouuid'];
    }

    public function createDocument(string $apiName, string $type, ?string $ouuid, array $body) : string
    {
        $response = $this->getApiClient($apiName)->initNewDocument($type, $body, $ouuid);

        if (! $response['success']) {
            throw new \Exception(isset($response['error'][0])?$response['error'][0]:'Create draft failed');
        }

        $response = $this->getApiClient($apiName)->finalize($type, $response['revision_id']);

        if (! $response['success']) {
            throw new \Exception(isset($response['error'][0])?$response['error'][0]:'Finalize draft failed');
        }
        return $response['ouuid'];
    }

    public function uploadFile(string $apiName, \SplFileInfo $file, $filename)
    {
        $response = $this->getApiClient($apiName)->postFile($file, $filename);
        //TODO: remove this hack once the ems back is returniong the file hash as parameter
        if (! isset($response[EmsFields::CONTENT_FILE_HASH_FIELD_]) && isset($response['url'])) {
            $output_array = [];
            preg_match('/\/data\/file\/view\/(?P<hash>.*)\?.*/', $response['url'], $output_array);
            if (isset($output_array['hash'])) {
                $response[EmsFields::CONTENT_FILE_HASH_FIELD_] = $output_array['hash'];
            }
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
        foreach ($this->clientRequests as $clientRequest) {
            if ($apiName === $clientRequest->getOption('[api][name]', false)) {
                return $clientRequest;
            }
        }

        throw new NotFoundHttpException();
    }

    private function getApiClient(string $clientName) : Client
    {
        foreach ($this->apiClients as $apiClient) {
            if ($clientName === $apiClient->getName()) {
                return $apiClient;
            }
        }

        throw new NotFoundHttpException();
    }
}
