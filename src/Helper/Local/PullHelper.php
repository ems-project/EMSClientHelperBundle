<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Local\File\RoutesFile;
use EMS\ClientHelperBundle\Helper\Local\File\TemplatesFile;
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
        $templatesFile = $this->pullTemplates($localEnvironment);
        $this->pullRoutes($localEnvironment, $templatesFile);
    }

    private function pullTranslations(LocalEnvironment $localEnvironment): void
    {
        $environment = $localEnvironment->getEnvironment();

        foreach ($this->translationBuilder->buildMessageCatalogues($environment) as $messageCatalogue) {
            $localEnvironment->dumpMessageCatalogue($messageCatalogue);
        }
    }

    private function pullTemplates(LocalEnvironment $localEnvironment): TemplatesFile
    {
        $templatesFile = new TemplatesFile();
        foreach ($this->templatingBuilder->buildTemplates($localEnvironment->getEnvironment()) as $template) {
            $localEnvironment->dumpTemplate($template);
            $templatesFile->addTemplate($template);
        }

        $localEnvironment->dumpTemplatesFile($templatesFile);

        return $templatesFile;
    }

    private function pullRoutes(LocalEnvironment $localEnvironment, TemplatesFile $templatesFile): void
    {
        $routesFile = new RoutesFile($templatesFile);
        $routeConfigs = $this->routingBuilder->buildRouteConfigs($localEnvironment->getEnvironment());

        foreach ($routeConfigs as $routeConfig) {
            $routesFile->addRouteConfig($routeConfig);
        }

        $localEnvironment->dumpRoutesFile($routesFile);
    }
}
