<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Exception\TemplatingException;
use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\Builder\BuilderDocumentInterface;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Environment\Environment;

final class TemplateBuilder extends AbstractBuilder
{
    public function isFresh(Environment $environment, TemplateName $templateName, int $time): bool
    {
        if ($environment->isLocalPulled()) {
            return $environment->getLocal()->getTemplates()->getByTemplateName($templateName)->isFresh($time);
        }

        return $this->getContentType($environment, $templateName)->isLastPublishedAfterTime($time);
    }

    public function buildTemplate(Environment $environment, TemplateName $templateName): TemplateDocument
    {
        $mapping = $this->getMappingConfig($templateName->getContentType());
        $contentType = $this->getContentType($environment, $templateName);

        $searchField = $templateName->getSearchField();
        $hit = $this->clientRequest->searchOne($contentType->getName(), [
            '_source' => [$mapping['name'], $mapping['code']],
            'query' => [
                'term' => [
                    ($searchField ?? $mapping['name']) => $templateName->getSearchValue(),
                ],
            ],
        ], $contentType->getEnvironment()->getAlias());

        return new TemplateDocument($hit['_id'], $hit['_source'], $mapping);
    }

    public function buildFiles(Environment $environment, string $directory): TemplateFiles
    {
        $contentTypes = \array_keys($this->getTemplatesConfig());
        $documents = $this->getDocuments($environment);

        return TemplateFiles::build($directory, $contentTypes, $documents);
    }

    /**
     * @return \Generator|BuilderDocumentInterface[]|TemplateDocument[]
     */
    public function getDocuments(Environment $environment): \Generator
    {
        $config = $this->getTemplatesConfig();

        foreach ($config as $name => $mapping) {
            if (null === $contentType = $this->clientRequest->getContentType($name, $environment)) {
                continue;
            }

            foreach ($this->search($contentType)->getDocuments() as $doc) {
                yield new TemplateDocument($doc->getId(), $doc->getSource(), $mapping);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getTemplatesConfig(): array
    {
        return $this->clientRequest->getOption('[templates]');
    }

    private function getContentType(Environment $environment, TemplateName $templateName): ContentType
    {
        $contentTypeName = $templateName->getContentType();

        if (null === $contentType = $this->clientRequest->getContentType($contentTypeName, $environment)) {
            throw new TemplatingException(\sprintf('Invalid contentType %s', $contentTypeName));
        }

        return $contentType;
    }

    /**
     * @return array<mixed>
     */
    public function getMappingConfig(string $contentType): array
    {
        $configTemplates = $this->getTemplatesConfig();
        $config = $configTemplates[$contentType] ?? null;

        if (null === $config) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        return $config;
    }
}
