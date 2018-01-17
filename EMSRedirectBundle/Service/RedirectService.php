<?php

namespace EMS\ClientHelperBundle\EMSRedirectBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\MissingTranslationException;
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
     * @var RedirectRouterServiceInterface
     */
    private $redirectRouter;

    /**
     * @var string
     */
    private $redirectType;

    /**
     * @param ClientRequest $clientRequest
     * @param RedirectRouterInterface $redirectRouter
     * @param string $redirectType
     */
    public function __construct(
        ClientRequest $clientRequest,
        $redirectRouter,
        $redirectType)
    {
        $interfaces = class_implements($redirectRouter);
        if (!in_array(RedirectRouterServiceInterface::class, $interfaces)) {
            $msg = "Argument 2 should implement RedirectRouterServiceInterface";
            throw new ContextErrorException($msg, 0, E_ERROR, __FILE__, __LINE__);
        }
        $this->clientRequest = $clientRequest;
        $this->redirectRouter = $redirectRouter;
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
            $source = $document['_source'];

            $linkedDocument = $this->getLinkedDocument($source['link_to']);

            return $this->redirectRouter->getPath(
                $linkedDocument,
                $locale,
                $source
            );
        } catch (MissingTranslationException $mex) {
            throw $mex;
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
                            'url_'.$locale => $uri
                        ]
                    ]
                ]
            ]
        ]);
    }
    
    /**
     * @param string $linkTo
     *
     * @return array|false
     */
    private function getLinkedDocument($linkTo)
    {
        preg_match('/(?P<type>.*):(?P<ouuid>.*)/i', $linkTo, $matches);
        
        if (!$matches) {
            return false;
        }
        
        return $this->clientRequest->get($matches['type'], $matches['ouuid']);
    }
}
