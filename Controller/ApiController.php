<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Api\ApiService;
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
    public function contentTypesAction($apiName)
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
    public function contentTypeAction(Request $request, $apiName, $contentType)
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
    public function documentAction($apiName, $contentType, $ouuid)
    {
        return $this->service->getDocument($apiName, $contentType, $ouuid)->getResponse();
    }
}
