<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Exception\SingleResultException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @todo remove
 */
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
