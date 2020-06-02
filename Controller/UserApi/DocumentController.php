<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller\UserApi;

use EMS\ClientHelperBundle\Service\UserApi\DocumentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class DocumentController
{
    /** @var DocumentService */
    private $service;

    public function __construct(DocumentService $service)
    {
        $this->service = $service;
    }

    public function show(string $contentType, string $ouuid, Request $request): JsonResponse
    {
        $context = [
            'authToken' => $request->headers->get('X-Auth-Token'),
        ];

        return new JsonResponse($this->service->getDocument($contentType, $ouuid, $context));
    }

    public function store(string $contentType, Request $request): JsonResponse
    {
        $context = [
            'authToken' => $request->headers->get('X-Auth-Token'),
            'body' => \json_decode($request->getContent(), true)
        ];

        return new JsonResponse($this->service->storeDocument($contentType, $context));
    }

    public function update(string $contentType, string $ouuid, Request $request): JsonResponse
    {
        $context = [
            'authToken' => $request->headers->get('X-Auth-Token'),
            'body' => \json_decode($request->getContent(), true)
        ];

        return new JsonResponse($this->service->updateDocument($contentType, $ouuid, $context));
    }

    public function merge(string $contentType, string $ouuid, Request $request): JsonResponse
    {
        $context = [
            'authToken' => $request->headers->get('X-Auth-Token'),
            'body' => \json_decode($request->getContent(), true)
        ];

        return new JsonResponse($this->service->mergeDocument($contentType, $ouuid, $context));
    }
}
