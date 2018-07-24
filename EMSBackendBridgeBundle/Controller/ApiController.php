<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Controller;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\ApiService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController
{
    /**
     * @Route("{apiName}/content-types", name="emsch_api_content_types")
     * @Method("GET")
     *
     * @param string $apiName
     *
     * @return JsonResponse
     */
    public function contentTypesAction(ApiService $apiService, $apiName)
    {
        return $this->getApi()->getContentTypes($apiName)->getResponse();
    }

    /**
     * @Route("{apiName}/content-types/{contentType}", name="emsch_api_content_type")
     * @Method("GET")
     *
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

        return $this->getApi()->getContentType($apiName, $contentType, $filter, $size, $scrollId)->getResponse();
    }

    /**
     * @Route("{apiName}/content-types/{contentType}/{ouuid}", name="emsch_api_document")
     * @Method("GET")
     *
     * @param string $apiName
     * @param string $contentType
     * @param string $ouuid
     *
     * @return JsonResponse
     */
    public function documentAction($apiName, $contentType, $ouuid)
    {
        return $this->getApi()->getDocument($apiName, $contentType, $ouuid)->getResponse();
    }

    /**
     * @return ApiService
     */
    private function getApi()
    {
        return $this->container->get('emsch.api.service');
    }
}
