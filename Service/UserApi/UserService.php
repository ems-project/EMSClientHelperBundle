<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

final class UserService extends UserApiService
{
    /**
     * @param array<mixed> $context
     * @return array<mixed>
     */
    public function getUsers(array $context): array
    {
        $client = $this->createClient(['X-Auth-Token' => $context['authToken']]);
        $response = $client->get(\sprintf('/api/user-profiles'));

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array<mixed> $context
     * @return array<string>
     */
    public function getProfile(array $context): array
    {
        $client = $this->createClient(['X-Auth-Token' => $context['authToken']]);
        $response = $client->get(\sprintf('/api/user-profile'));

        return \json_decode($response->getBody()->getContents(), true);
    }
}
