<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Api\ApiService;
use EMS\CommonBundle\Helper\EmsFields;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController
{
    /**
     * @var ApiService
     */
    private $service;

    /**
     * @param ApiService $service
     */
    public function __construct(ApiService $service)
    {
        $this->service = $service;
    }

    /**
     * @param string $apiName
     *
     * @return JsonResponse
     */
    public function contentTypes($apiName)
    {
        return $this->service->getContentTypes($apiName)->getResponse();
    }

    /**
     * @param Request $request
     * @param string  $apiName
     * @param string  $contentType
     *
     * @return JsonResponse
     */
    public function contentType(Request $request, $apiName, $contentType)
    {
        $scrollId = $request->query->get('scroll');
        $size = $request->query->get('size');
        $filter = $request->query->get('filter', []);

        return $this->service->getContentType($apiName, $contentType, $filter, $size, $scrollId)->getResponse();
    }

    /**
     * @param string $apiName
     * @param string $contentType
     * @param string $ouuid
     *
     * @return JsonResponse
     */
    public function document($apiName, $contentType, $ouuid)
    {
        return $this->service->getDocument($apiName, $contentType, $ouuid)->getResponse();
    }

    public function updateDocumentFromForm(Request $request, string $apiName, string $contentType, ?string $ouuid, string $redirectUrl) : RedirectResponse
    {
        $body = ($request->request->all());

        /** @var string $key */
        /** @var UploadedFile $file */
        foreach ($request->files as $key => $file) {
            if ($file !== null) {
                $response = $this->service->uploadFile($apiName, $file, $file->getClientOriginalName());
                if (!$response['uploaded'] || !isset($response[EmsFields::CONTENT_FILE_HASH_FIELD_])) {
                    throw new \Exception('File hash not found or file not uploaded');
                }
                $body[$key] = [
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
        }

        $ouuid = $this->service->updateDocument($apiName, $contentType, $ouuid, $body);

        $url = str_replace('%ouuid%', $ouuid, $redirectUrl);
        $url = str_replace('%contenttype%', $contentType, $url);
        return new RedirectResponse($url);
    }
}
