<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller\UserApi;

use EMS\ClientHelperBundle\Service\UserApi\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ProfileController
{
    /** @var UserService */
    private $userService;

    public function __construct(UserService $service)
    {
        $this->userService = $service;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse($this->userService->getProfile($request));
    }
}
