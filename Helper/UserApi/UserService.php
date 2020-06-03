<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\UserApi;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class UserService extends UserApiService
{
    public function getUsers(Request $request): JsonResponse
    {
        $client = $this->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);
        $response = $client->get(\sprintf('/api/user-profiles'));

        return JsonResponse::fromJsonString($response->getBody()->getContents());
    }

    public function getProfile(Request $request): JsonResponse
    {
        $client = $this->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);
        $response = $client->get(\sprintf('/api/user-profile'));

        return JsonResponse::fromJsonString($response->getBody()->getContents());
    }
}
