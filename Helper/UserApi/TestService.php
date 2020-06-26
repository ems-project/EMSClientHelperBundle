<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\UserApi;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class TestService
{
    /** @var ClientFactory */
    private $client;

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    public function test(Request $request): JsonResponse
    {
        $client = $this->client->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);
        $response = $client->get('/api/test');

        return JsonResponse::fromJsonString($response->getBody()->getContents());
    }
}
