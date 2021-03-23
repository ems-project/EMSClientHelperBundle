<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Exception\TemplatingException;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Environment\Environment;

final class Templates
{
    private Environment $environment;
    private ClientRequest $clientRequest;
    /** @var array<mixed> */
    private array $config = [];

    public function __construct(ClientRequest $clientRequest, Environment $environment)
    {
        $this->environment = $environment;
        $this->clientRequest = $clientRequest;
        $config = $clientRequest->getOption('[templates]');

        if (null === $config) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        foreach ($config as $contentTypeName => $mapping) {
            if (null === $clientRequest->getContentType($contentTypeName, $environment)) {
                throw new TemplatingException(\sprintf('Invalid contentType %s', $contentTypeName));
            }

            $this->config[$contentTypeName] = ['mapping' => $mapping];
        }
    }

    /**
     * @return array<mixed>
     */
    public function getMapping(string $contentTypeName): array
    {
        if (!isset($this->config[$contentTypeName]['mapping'])) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        return $this->config[$contentTypeName]['mapping'];
    }

    public function getContentType(string $contentTypeName): ContentType
    {
        if (!isset($this->config[$contentTypeName])) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        if (null === $contentType = $this->clientRequest->getContentType($contentTypeName, $this->environment)) {
            throw new TemplatingException(\sprintf('Invalid contentType %s', $contentTypeName));
        }

        return $contentType;
    }

    /**
     * @return ContentType[]
     */
    public function getContentTypes(): array
    {
        $contentTypeNames = \array_keys($this->config);
        $contentTypes = [];

        foreach ($contentTypeNames as $contentTypeName) {
            if (null !== $contentType = $this->clientRequest->getContentType($contentTypeName, $this->environment)) {
                $contentTypes[] = $contentType;
            }
        }

        return $contentTypes;
    }
}
