<?php

namespace EMS\ClientHelperBundle\Helper\Api;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\CommonBundle\Helper\EmsFields;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiService
{
    const EMS_AJAX_MESSAGE_LEVELS = ['error', 'warning'];

    /** @var ClientRequest[] */
    private $clientRequests;

    /** @var Client[] */
    private $apiClients;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var \Twig_Environment */
    private $twig;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger, \Twig_Environment $twig, UrlGeneratorInterface $urlGenerator, iterable $clientRequests = [], iterable $apiClients = [])
    {
        $this->logger = $logger;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->clientRequests = $clientRequests;
        $this->apiClients = $apiClients;
    }

    public function treatFormRequest(Request $request, string $apiName, string $validationTemplate = null)
    {
        $body = $request->request->all();
        $body = $this->treatFiles($body, $apiName, $request->files);
        if (null !== $validationTemplate) {
            return \json_decode($this->twig->render($validationTemplate, [
                'document' => $body,
            ]), true);
        }

        return $body;
    }

    /**
     * @param array<string,mixed>                $body
     * @param FileBag<array>|array<string,mixed> $files
     *
     * @return array<string,mixed>
     */
    private function treatFiles(array $body, string $apiName, $files): array
    {
        /** @var string $fieldKey */
        foreach ($files as $fieldKey => $fileField) {
            if (\is_array($fileField)) {
                /** @var string $pos */
                foreach ($fileField as $pos => $collectionOfFields) {
                    /** @var string $fileKey */
                    foreach ($collectionOfFields as $fileKey => $file) {
                        if (\is_array($file)) {
                            $body[$fieldKey][$pos] = $this->treatFiles($body[$fieldKey][$pos], $apiName, $collectionOfFields);
                        } else {
                            if (null !== $file) {
                                $body[$fieldKey][$pos][$fileKey] = $this->createContentFileHashField($apiName, $file);
                            }
                        }
                    }
                }
            } else {
                if (null !== $fileField) {
                    $body[$fieldKey] = $this->createContentFileHashField($apiName, $fileField);
                }
            }
        }

        return $body;
    }

    /**
     * @throws \Exception
     *
     * @return array<string, mixed>
     */
    private function createContentFileHashField(string $apiName, UploadedFile $file): array
    {
        $response = $this->uploadFile($apiName, $file, $file->getClientOriginalName());
        if (!$response['uploaded'] || !isset($response[EmsFields::CONTENT_FILE_HASH_FIELD_])) {
            throw new \Exception('File hash not found or file not uploaded');
        }

        return [
            EmsFields::CONTENT_FILE_HASH_FIELD => $response[EmsFields::CONTENT_FILE_HASH_FIELD_],
            EmsFields::CONTENT_FILE_HASH_FIELD_ => $response[EmsFields::CONTENT_FILE_HASH_FIELD_],
            EmsFields::CONTENT_FILE_NAME_FIELD => $file->getClientOriginalName(),
            EmsFields::CONTENT_FILE_NAME_FIELD_ => $file->getClientOriginalName(),
            EmsFields::CONTENT_FILE_SIZE_FIELD => $file->getSize(),
            EmsFields::CONTENT_FILE_SIZE_FIELD_ => $file->getSize(),
            EmsFields::CONTENT_MIME_TYPE_FIELD => $file->getMimeType(),
            EmsFields::CONTENT_MIME_TYPE_FIELD_ => $file->getMimeType(),
        ];
    }

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
                'contentType' => $contentType,
            ]);

            $response->addData('content_types', [
                'name' => $contentType,
                '_links' => [
                    Response::createLink('self', $url, $contentType),
                ],
            ]);
        }

        return $response;
    }

    /**
     * @param string $apiName
     * @param string $contentType
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
            $url = $this->urlGenerator->generate('emsch_api_document', [
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

    public function updateDocument(string $apiName, string $type, string $ouuid, array $body): string
    {
        $apiClient = $this->getApiClient($apiName);
        $response = $apiClient->updateDocument($type, $ouuid, $body);

        return $this->finalizeResponse($apiClient, $response, $type, $ouuid);
    }

    public function createDocument(string $apiName, string $type, ?string $ouuid, array $body): string
    {
        $apiClient = $this->getApiClient($apiName);
        $response = $apiClient->initNewDocument($type, $body, $ouuid);

        return $this->finalizeResponse($apiClient, $response, $type, $ouuid);
    }

    private function finalizeResponse(Client $apiClient, array $response, string $type, ?string $ouuid): string
    {
        if (!$response['success']) {
            foreach (ApiService::EMS_AJAX_MESSAGE_LEVELS as $level) {
                if (isset($response[$level][0])) {
                    throw new \Exception($response[$level][0]);
                }
            }
            throw new \Exception('Initialize draft failed');
        }

        $revisionId = $response['revision_id'];
        $response = $apiClient->finalize($type, $revisionId);

        if (!$response['success']) {
            try {
                $apiClient->discardDraft($type, $revisionId);
            } catch (\Exception $e) {
                $this->logger->warning('emsch.api_service.discard_exception', [
                    'ouuid' => $ouuid,
                    'type' => $type,
                    'revision_id' => $revisionId,
                ]);
            }

            foreach (ApiService::EMS_AJAX_MESSAGE_LEVELS as $level) {
                if (isset($response[$level][0])) {
                    throw new \Exception($response[$level][0]);
                }
            }
            throw new \Exception('Finalize draft failed');
        }

        return $response['ouuid'];
    }

    public function uploadFile(string $apiName, \SplFileInfo $file, $filename)
    {
        $response = $this->getApiClient($apiName)->postFile($file, $filename);
        //TODO: remove this hack once the ems back is returning the file hash as parameter
        if (!isset($response[EmsFields::CONTENT_FILE_HASH_FIELD_]) && isset($response['url'])) {
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
            'contentType' => $contentType,
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

    public function getApiClient(string $clientName): Client
    {
        foreach ($this->apiClients as $apiClient) {
            if ($clientName === $apiClient->getName()) {
                return $apiClient;
            }
        }

        throw new NotFoundHttpException();
    }
}
