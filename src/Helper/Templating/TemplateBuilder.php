<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Exception\TemplatingException;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use Psr\Log\LoggerInterface;

final class TemplateBuilder
{
    private ClientRequest $clientRequest;
    private LoggerInterface $logger;

    public function __construct(ClientRequestManager $manager, LoggerInterface $logger)
    {
        $this->clientRequest = $manager->getDefault();
        $this->logger = $logger;
    }

    public function isFresh(Environment $environment, Template $template, int $time): bool
    {
        return $this->getContentType($environment, $template)->isLastPublishedAfterTime($time);
    }

    public function buildTemplate(Environment $environment, Template $template): string
    {
        $mapping = $this->getMappingConfig($template);
        $contentType = $this->getContentType($environment, $template);

        $nameField = $template->getSearchField();
        $codeField = $mapping['code'];

        $document = $this->clientRequest->searchOne($contentType->getName(), [
            'query' => [
                'term' => [
                    ($nameField ?? $mapping['name']) => $template->getSearchValue(),
                ],
            ],
            '_source' => [$codeField],
        ]);

        return $document['_source'][$codeField] ?? '';
    }

    private function getContentType(Environment $environment, Template $template): ContentType
    {
        $contentTypeName = $template->getContentType();

        if (null === $contentType = $this->clientRequest->getContentType($contentTypeName, $environment)) {
            throw new TemplatingException(\sprintf('Invalid contentType %s', $contentTypeName));
        }

        return $contentType;
    }

    /**
     * @return array<mixed>
     */
    private function getMappingConfig(Template $template): array
    {
        $configTemplates = $this->clientRequest->getOption('[templates]');
        $config = $configTemplates[$template->getContentType()] ?? null;

        if (null === $config) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        return $config;
    }
}
