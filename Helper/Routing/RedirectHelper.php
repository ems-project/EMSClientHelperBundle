<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Request\ClientRequest;
use EMS\ClientHelperBundle\Helper\Routing\Url\Transformer;
use Symfony\Component\HttpFoundation\Request;

class RedirectHelper
{
    /**
     * @var ClientRequest
     */
    protected $clientRequest;

    /**
     * @var Transformer
     */
    private $transformer;

    /**
     * @var string
     */
    private $redirectType;

    /**
     * @param ClientRequest $clientRequest
     * @param Transformer   $transformer
     * @param string        $redirectType
     */
    public function __construct(ClientRequest $clientRequest, Transformer $transformer, string $redirectType)
    {
        $this->clientRequest = $clientRequest;
        $this->transformer = $transformer;
        $this->redirectType = $redirectType;
    }

    /**
     * @param Request $request
     *
     * @return bool|null|string|string[]
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

            return $this->transformer->transform('ems://object:' . $linkTo, $locale);

        } catch (\Exception $ex) {
            return false;
        }
    }
    
    /**
     * @param string $uri
     * @param string $locale
     *
     * @return array
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
