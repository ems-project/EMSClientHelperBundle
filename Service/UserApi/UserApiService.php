<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

use GuzzleHttp\Client;

abstract class UserApiService
{
    /** @var string */
    private $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param array<string> $headers
     * @return Client
     */
    protected function createClient(array $headers = []): Client
    {
        return new Client([
            'base_uri' => $this->baseUrl,
            'headers'  => $headers,
            'timeout'  => 30,
            'allow_redirects' => false,
        ]);
    }
}
