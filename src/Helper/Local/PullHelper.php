<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Routing\RoutingBuilder;
use EMS\ClientHelperBundle\Helper\Templating\TemplateBuilder;
use EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder;
use Psr\Log\LoggerInterface;

final class PullHelper
{
    private LocalHelper $localHelper;
    private TemplateBuilder $templatingBuilder;
    private TranslationBuilder $translationBuilder;
    private RoutingBuilder $routingBuilder;
    private LoggerInterface $logger;

    public function __construct(
        LocalHelper $localHelper,
        TemplateBuilder $templatingBuilder,
        TranslationBuilder $translationBuilder,
        RoutingBuilder $routingBuilder,
        LoggerInterface $logger
    ) {
        $this->localHelper = $localHelper;
        $this->templatingBuilder = $templatingBuilder;
        $this->translationBuilder = $translationBuilder;
        $this->routingBuilder = $routingBuilder;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function pull(Environment $environment): void
    {
        $localEnvironment = $this->localHelper->local($environment);
        $localEnvironment->setLogger($this->logger);

        $this->pullTranslations($localEnvironment);
        $templateMapping = $this->pullTemplates($localEnvironment);
        $this->pullRoutes($localEnvironment, $templateMapping);
    }

    private function pullTranslations(LocalEnvironment $localEnvironment): void
    {
        $environment = $localEnvironment->getEnvironment();

        foreach ($this->translationBuilder->buildMessageCatalogues($environment) as $messageCatalogue) {
            $localEnvironment->dumpMessageCatalogue($messageCatalogue);
        }
    }

    /**
     * @return array<string, string>
     */
    private function pullTemplates(LocalEnvironment $localEnvironment): array
    {
        $mapping = [];
        foreach ($this->templatingBuilder->buildTemplates($localEnvironment->getEnvironment()) as $template) {
            $localEnvironment->dumpTemplate($template);
            $mapping[$template->getEmschNameId()] = $template->getEmschName();
        }

        $localEnvironment->dumpJsonTemplates($mapping);

        return $mapping;
    }

    /**
     * @param array<string, string> $templateMapping
     */
    private function pullRoutes(LocalEnvironment $localEnvironment, array $templateMapping): void
    {
        $routes = [];
        $routeConfigs = $this->routingBuilder->buildRouteConfigs($localEnvironment->getEnvironment());

        foreach ($routeConfigs as $routeConfig) {
            $route = $routeConfig->toArray();

            if (isset($route['template_static'])) {
                $route['template_static'] = $templateMapping[$route['template_static']] ?? $route['template_static'];
            }

            $routes[$routeConfig->getName()] = $route;
        }

        $localEnvironment->dumpJsonRoutes($routes);
    }
}
