<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Exception\TemplatingException;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Environment\Environment;

final class Templates
{
    private array $templates = [];

    public function __construct(ClientRequest $clientRequest, Environment $environment)
    {
        $config = $clientRequest->getOption('[templates]');

        if (null === $config) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        foreach ($config as $contentTypeName => $mapping) {
            if (null === $contentType = $clientRequest->getContentType($contentTypeName, $environment)) {
                throw new TemplatingException(\sprintf('Invalid contentType %s', $contentTypeName));
            }

            $this->templates[$contentTypeName] = ['contentType' => $contentType, 'mapping' => $mapping];
        }
    }

    public function getMapping(string $contentTypeName): array
    {
        $mapping = $this->templates[$contentTypeName]['mapping'] ?? false;

        if (!$mapping) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        return $mapping;
    }

    public function getContentType(string $contentTypeName): ContentType
    {
        $contentType = $this->templates[$contentTypeName]['contentType'] ?? false;

        if (!$contentType) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        return $contentType;
    }

    /**
     * @return ContentType[]
     */
    public function getContentTypes(): array
    {
        return \array_values(\array_map(fn (array $template) => $template['contentType'], $this->templates));
    }
}
