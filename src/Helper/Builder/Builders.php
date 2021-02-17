<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Builder;

use EMS\ClientHelperBundle\Helper\Routing\RoutingBuilder;
use EMS\ClientHelperBundle\Helper\Templating\TemplateBuilder;
use EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder;

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
}
