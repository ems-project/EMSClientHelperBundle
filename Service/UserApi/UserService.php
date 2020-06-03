<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

use Symfony\Component\HttpFoundation\Request;

final class UserService extends UserApiService
{
    /**
     * @return array<mixed>
     */
    public function getUsers(Request $request): array
    {
        $client = $this->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);
        $response = $client->get(\sprintf('/api/user-profiles'));

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<string>
     */
    public function getProfile(Request $request): array
    {
        $client = $this->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);
        $response = $client->get(\sprintf('/api/user-profile'));

        return \json_decode($response->getBody()->getContents(), true);
    }
}
