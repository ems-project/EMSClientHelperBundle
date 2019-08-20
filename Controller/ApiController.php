<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Api\ApiService;
use EMS\CommonBundle\Helper\EmsFields;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class ApiController
{
    /**
     * @var ApiService
     */
    private $service;
    /**
     * @var CsrfTokenManager
     */
    private $csrfTokenManager;

    public function __construct(ApiService $service, CsrfTokenManager $csrfTokenManager)
    {
        $this->service = $service;
        $this->csrfTokenManager = $csrfTokenManager;
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

    private function treatFormRequest(Request $request, string $apiName)
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
        return $body;
    }


    private function validateHashcash(Request $request, string $csrfId, int $hashcashLevel, string $hashAlgo)
    {
        $hashcash = $request->headers->get('X-Hashcash');
        if ($hashcash === null) {
            throw new AccessDeniedHttpException('Unrecognized user');
        }

        $tokens = explode('|', $hashcash);

        if (intval($tokens[0]) < $hashcashLevel) {
            throw new AccessDeniedHttpException('Insufficient security level by definition');
        }

        if (!preg_match(sprintf('/^0{%d}/', $hashcashLevel), hash($hashAlgo, $hashcash))) {
            throw new AccessDeniedHttpException('Insufficient security level');
        }

        if ($this->csrfTokenManager->getToken($csrfId)->getValue() !== $tokens[1]) {
            throw new AccessDeniedHttpException('Unrecognized key');
        }
    }

    public function handleJsonPostRequest(Request $request, string $apiName, string $contentType, ?string $ouuid, string $csrfId, string $validationTemplate, int $hashcashLevel, string $hashAlgo)
    {
        $this->validateHashcash($request, $csrfId, $hashcashLevel, $hashAlgo);
        dump($request->getContent());
    }

    public function createDocumentFromForm(Request $request, string $apiName, string $contentType, ?string $ouuid, string $redirectUrl) : RedirectResponse
    {
        $body = $this->treatFormRequest($request, $apiName);

        $ouuid = $this->service->createDocument($apiName, $contentType, $ouuid, $body);

        $url = str_replace('%ouuid%', $ouuid, $redirectUrl);
        $url = str_replace('%contenttype%', $contentType, $url);
        return new RedirectResponse($url);
    }

    public function updateDocumentFromForm(Request $request, string $apiName, string $contentType, string $ouuid, string $redirectUrl) : RedirectResponse
    {
        $body = $this->treatFormRequest($request, $apiName);

        $ouuid = $this->service->updateDocument($apiName, $contentType, $ouuid, $body);

        $url = str_replace('%ouuid%', $ouuid, $redirectUrl);
        $url = str_replace('%contenttype%', $contentType, $url);
        return new RedirectResponse($url);
    }
}
