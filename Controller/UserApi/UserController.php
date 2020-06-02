<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller\UserApi;

use EMS\ClientHelperBundle\Service\UserApi\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class UserController
{
    /** @var UserService */
    private $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $context = [
            'authToken' => $request->headers->get('X-Auth-Token'),
        ];

        return new JsonResponse($this->service->getUsers($context));
    }
}
