<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Controller;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\ApiService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ZeController extends AbstractController
{
    /**
     * @Route("/{slug}", defaults={"slug": false}, requirements={"slug": ".+"}, name="emsch_ze_page")
     */
    public function pageAction($slug, Request $request, ClientRequest $client)
    {
        
        try{
            $object = $client->getPage($slug?'/'. $slug:'Homepage');
            return $this->render('@EMSCH/'.$object['template'], [
                'source' => $object,
            ]);
        }
        catch (SingleResultException $e)
        {

        }
        throw new NotFoundHttpException();
    }
}
