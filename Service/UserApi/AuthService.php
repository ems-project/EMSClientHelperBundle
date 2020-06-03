<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

use Symfony\Component\HttpFoundation\Request;

final class AuthService extends UserApiService
{
    public function getUserAuthToken(Request $request): string
    {
        $credentials = [
            'username' => $request->get('username'),
            'password' => $request->get('password'),
        ];

        $client = $this->createClient(['Content-Type' => 'application/json']);
        $response = $client->post('auth-token', ['body' => \json_encode($credentials)]);

        return $response->getBody()->getContents();
    }
}
