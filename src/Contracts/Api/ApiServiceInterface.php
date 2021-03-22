<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Contracts\Api;

interface ApiServiceInterface
{
    /**
     * Used in the formBundle: HttpEndpointType.
     */
    public function getApiClient(string $clientName): ApiClientInterface;
}
