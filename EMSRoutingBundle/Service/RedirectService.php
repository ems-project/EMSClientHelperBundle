<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\MissingTranslationException;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\RoutingService;
use Symfony\Component\Debug\Exception\ContextErrorException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class RedirectService
{
    /**
     * @var ClientRequest
     */
    protected $clientRequest;

    /**
     * @var string
     */
    private $redirectType;

    /**
     * @var RoutingService
     */
    private $routingService;

    /**
     * @param RoutingService $routingService
     */
    public function __construct(RoutingService $routingService)
    {
        $this->routingService = $routingService;
    }

    /**
     * @param ClientRequest $clientRequest
     */
    public function setClientRequest(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    /**
     * @param string $redirectType
     */
    public function setRedirectType($redirectType)
    {
        $this->redirectType = $redirectType;
    }
    
    /**
     * @param Request $request
     */
    public function getForwardUri(Request $request)
    {
        try {
            $locale = $request->getLocale();
            $document = $this->getRedirectDocument(
                $request->getPathInfo(),
                $locale
            );
            $linkTo = $document['_source']['link_to'];

            return $this->routingService->transform('ems://object:' . $linkTo, $locale);

        } catch (\Exception $ex) {
            return false;
        }
    }
    
    /**
     * @param string $uri
     * @param string $locale
     *
     * @return array
     * 
     * @throws Exception
     */
    private function getRedirectDocument($uri, $locale)
    {
        return $this->clientRequest->searchOne($this->redirectType, [
            'query' => [
                'bool' => [
                    'must' => [
                        'term' => [
                            'url_'.$locale => urldecode($uri)
                        ]
                    ]
                ]
            ]
        ]);
    }
}
