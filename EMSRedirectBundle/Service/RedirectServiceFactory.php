<?php
/**
 * Created by PhpStorm.
 * User: dameert
 * Date: 18/08/17
 * Time: 00:01
 */

namespace EMS\ClientHelperBundle\EMSRedirectBundle\Service;


use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSRedirectBundle\EventSubscriber\KernelSubscriber;

class RedirectServiceFactory
{
    public static function create(
        ClientRequest $clientRequest,
        RedirectRouterServiceInterface $redirectRouter,
        $redirectType
    )
    {
        $redirectService = new RedirectService(
            $clientRequest,
            $redirectRouter,
            $redirectType
        );

        return $redirectService;
    }
}