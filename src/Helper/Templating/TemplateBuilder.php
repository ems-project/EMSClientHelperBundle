<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Helper\Builder\AbstractBuilder;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Environment\Environment;

final class TemplateBuilder extends AbstractBuilder
{
    public function buildTemplate(Environment $environment, TemplateName $templateName): TemplateDocument
    {
        $templates = $this->getTemplates($environment);
        $contentType = $templates->getContentType($templateName->getContentType());
        $mapping = $templates->getMapping($templateName->getContentType());

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

    public function buildFiles(Environment $environment, string $directory): void
    {
        $templates = $this->getTemplates($environment);
        $contentTypes = $templates->getContentTypes();

        $contentTypeNames = \array_map(fn (ContentType $contentType) => $contentType->getName(), $contentTypes);
        $documents = $this->getDocuments($environment);

        TemplateFiles::build($directory, $contentTypeNames, $documents);
    }

    /**
     * @return \Generator|TemplateDocument[]
     */
    public function getDocuments(Environment $environment): \Generator
    {
        $templates = $this->getTemplates($environment);

        foreach ($templates->getContentTypes() as $contentType) {
            $mapping = $templates->getMapping($contentType->getName());

            foreach ($this->search($contentType)->getDocuments() as $doc) {
                yield new TemplateDocument($doc->getId(), $doc->getSource(), $mapping);
            }
        }
    }

    public function getTemplates(Environment $environment): Templates
    {
        static $templates = null;

        if (null === $templates) {
            $templates = new Templates($this->clientRequest, $environment);
        }

        return $templates;
    }

    public function isFresh(Environment $environment, TemplateName $templateName, int $time): bool
    {
        if ($environment->isLocalPulled()) {
            return $environment->getLocal()->getTemplates()->getByTemplateName($templateName)->isFresh($time);
        }

        $templates = $this->getTemplates($environment);

        return $templates->getContentType($templateName->getContentType())->isLastPublishedAfterTime($time);
    }
}
