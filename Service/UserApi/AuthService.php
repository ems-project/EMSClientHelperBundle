<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

final class AuthService extends UserApiService
{
    /**
     * @param array<string> $credentials
     * @return string
     */
    public function getUserAuthToken(array $credentials): string
    {
        $client = $this->createClient(['Content-Type' => 'application/json']);
        $response = $client->post('auth-token', ['body' => \json_encode($credentials)]);

        return $response->getBody()->getContents();
    }
}
