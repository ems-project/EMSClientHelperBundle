<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

use Psr\Log\LoggerInterface;

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

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param iterable|ClientRequest[] $clientRequests
     * @param LoggerInterface          $logger
     */
    public function __construct(iterable $clientRequests, LoggerInterface $logger)
    {
        $this->logger = $logger;

        foreach ($clientRequests as $clientRequest) {
            $this->clientRequests[$clientRequest->getName()] = $clientRequest;

            if ($clientRequest->getOption('[default]', false)) {
                $this->default = $clientRequest;
            }
        }
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
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
