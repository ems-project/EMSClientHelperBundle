<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Contracts\Api;

interface ApiClientInterface
{
    /**
     * Used in the formBundle: HttpEndpointType.
     */
    public function createFormVerification(string $value): ?string;
}
