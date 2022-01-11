<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Builder;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Routing\RoutingBuilder;
use EMS\ClientHelperBundle\Helper\Templating\TemplateBuilder;
use EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder;
use EMS\CommonBundle\Common\Standard\Hash;

final class Builders
{
    private RoutingBuilder $routing;
    private TemplateBuilder $templating;
    private TranslationBuilder $translation;

    public function __construct(
        RoutingBuilder $routingBuilder,
        TemplateBuilder $templating,
        TranslationBuilder $translation
    ) {
        $this->routing = $routingBuilder;
        $this->templating = $templating;
        $this->translation = $translation;
    }

    /**
     * Returns the combined cache validity tags of all used contentTypes.
     */
    public function getVersion(Environment $environment): string
    {
        $contentTypes = $this->getContentTypes($environment);

        $cacheValidityTags = \array_reduce(
            $contentTypes,
            fn (string $key, ContentType $contentType) => $key.$contentType->getCacheValidityTag(),
            ''
        );

        return Hash::string($cacheValidityTags);
    }

    public function build(Environment $environment, string $directory): void
    {
        $this->translation->buildFiles($environment, $directory);
        $this->templating->buildFiles($environment, $directory);
        $this->routing->buildFiles($environment, $directory);
    }

    public function routing(): RoutingBuilder
    {
        return $this->routing;
    }

    public function templating(): TemplateBuilder
    {
        return $this->templating;
    }

    public function translation(): TranslationBuilder
    {
        return $this->translation;
    }

    /**
     * @return ContentType[]
     */
    private function getContentTypes(Environment $environment): array
    {
        return \array_filter([
            $this->translation->getContentType($environment),
            $this->routing->getContentType($environment),
            ...$this->templating->getTemplates($environment)->getContentTypes(),
        ]);
    }
}
