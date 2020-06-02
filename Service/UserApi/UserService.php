<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

final class UserService extends UserApiService
{
    public function getUsers(array $context): array
    {
        $client = $this->createClient(['X-Auth-Token' => $context['authToken']]);
        $response = $client->get(\sprintf('/api/user-profiles'));

        return \json_decode($response->getBody()->getContents(), true);
    }

    public function getProfile(array $context): array
    {
        $client = $this->createClient(['X-Auth-Token' => $context['authToken']]);
        $response = $client->get(\sprintf('/api/user-profile'));

        return \json_decode($response->getBody()->getContents(), true);
    }
}
