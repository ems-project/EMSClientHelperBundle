<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Routing\Url\Transformer;
use EMS\ClientHelperBundle\Helper\Twig\TwigException;
use Symfony\Component\HttpFoundation\Request;

final class RedirectHelper
{
    private ClientRequest $clientRequest;
    private Transformer $transformer;
    private string $redirectType;

    public function __construct(ClientRequest $clientRequest, Transformer $transformer, ?string $redirectType = null)
    {
        $this->clientRequest = $clientRequest;
        $this->transformer = $transformer;
        $this->redirectType = $redirectType ?? 'redirect';
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

            return $this->transformer->transform('ems://object:'.$linkTo, $locale);
        } catch (TwigException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @return array<mixed>
     */
    private function getRedirectDocument(string $uri, string $locale): array
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
