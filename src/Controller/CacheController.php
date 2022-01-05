<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CacheController
{


    public function __invoke(Request $request)
    {
        return new Response('okay', Response::HTTP_ACCEPTED);
    }

}