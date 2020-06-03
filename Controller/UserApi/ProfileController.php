<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller\UserApi;

use EMS\ClientHelperBundle\Helper\UserApi\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ProfileController
{
    /** @var UserService */
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->userService->getProfile($request);
    }
}
