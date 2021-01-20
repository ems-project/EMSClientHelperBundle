<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Exception\TemplatingException;
use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Environment\Environment;

final class TemplateBuilder extends AbstractBuilder
{
    public function isFresh(Environment $environment, TemplateName $templateName, int $time): bool
    {
        return $this->getContentType($environment, $templateName)->isLastPublishedAfterTime($time);
    }

    public function buildTemplate(Environment $environment, TemplateName $templateName): Template
    {
        $mapping = $this->getMappingConfig($templateName);
        $contentType = $this->getContentType($environment, $templateName);

        $searchField = $templateName->getSearchField();
        $hit = $this->clientRequest->searchOne($contentType->getName(), [
            'query' => [
                'term' => [
                    ($searchField ?? $mapping['name']) => $templateName->getSearchValue(),
                ],
            ],
            '_source' => [$mapping['name'], $mapping['code']],
        ]);

        return Template::fromHit($hit, $mapping);
    }

    /**
     * @return \Generator|Template[]
     */
    public function buildTemplates(Environment $environment): \Generator
    {
        $config = $this->getTemplatesConfig();

        foreach ($config as $name => $mapping) {
            if (null === $contentType = $this->clientRequest->getContentType($name, $environment)) {
                continue;
            }

            $scroll = $this->clientRequest->scrollAll([
                'size' => 100,
                'type' => $contentType->getName(),
                'sort' => ['_doc'],
            ], '5s', $contentType->getEnvironment()->getAlias());

            foreach ($scroll as $hit) {
                yield Template::fromHit($hit, $mapping);
            }
        }
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
    private function getTemplatesConfig(): array
    {
        return $this->clientRequest->getOption('[templates]');
    }

    /**
     * @return array<mixed>
     */
    private function getMappingConfig(TemplateName $templateName): array
    {
        $configTemplates = $this->getTemplatesConfig();
        $config = $configTemplates[$templateName->getContentType()] ?? null;

        if (null === $config) {
            throw new TemplatingException('Missing config EMSCH_TEMPLATES');
        }

        return $config;
    }
}
