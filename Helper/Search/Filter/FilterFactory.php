<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Search\Filter;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;

final class FilterFactory
{
    /** @var ClientRequest */
    private $clientRequest;

    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    public function create(string $name, array $options): Filter
    {
        return new Filter($this->clientRequest, $name, new Options($options));
    }
}
