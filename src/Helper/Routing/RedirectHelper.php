<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Routing\Url\Transformer;
use EMS\ClientHelperBundle\Helper\Twig\TwigException;
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
     * @param string $redirectType
     */
    public function __construct(ClientRequest $clientRequest, Transformer $transformer, $redirectType)
    {
        $this->clientRequest = $clientRequest;
        $this->transformer = $transformer;
        $this->redirectType = $redirectType ?: 'redirect';
    }

    /**
     * @return bool|string|string[]|null
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

            return $this->transformer->transform('ems://object:'.$linkTo, ['locale' => $locale]);
        } catch (TwigException $ex) {
            throw $ex;
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
                            'url_'.$locale => \urldecode($uri),
                        ],
                    ],
                ],
            ],
        ]);
    }
}
