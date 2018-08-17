<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

class ClientRequestManager
{
    /**
     * @var ClientRequest[]
     */
    private $clientRequests = [];

    /**
     * @var ClientRequest
     */
    private $default;

    /**
     * @param iterable|ClientRequest[] $clientRequests
     */
    public function __construct(iterable $clientRequests)
    {
        foreach ($clientRequests as $clientRequest) {
            $this->clientRequests[$clientRequest->getName()] = $clientRequest;

            if ($clientRequest->getOption('[default]', false)) {
                $this->default = $clientRequest;
            }
        }
    }

    /**
     * @return ClientRequest
     */
    public function getDefault(): ClientRequest
    {
        return $this->default;
    }

    /**
     * @return ClientRequest[]
     */
    public function all(): array
    {
        return $this->clientRequests;
    }

    /**
     * @param string $name
     *
     * @return ClientRequest
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $name): ClientRequest
    {
        if (!isset($this->clientRequests[$name])) {
            throw new \InvalidArgumentException(sprintf('Client request %s service not found!', $name));
        }

        return $this->clientRequests[$name];
    }
}
