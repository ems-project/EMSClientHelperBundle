<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use EMS\ClientHelperBundle\Helper\Local\File\RoutesFile;
use EMS\ClientHelperBundle\Helper\Local\File\TemplatesFile;
use Psr\Log\LoggerInterface;

final class PullHelper
{
    private Builders $builders;
    private LoggerInterface $logger;

    public function __construct(Builders $builders, LoggerInterface $logger)
    {
        $this->builders = $builders;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function pull(LocalEnvironment $localEnvironment): void
    {
        $localEnvironment->setLogger($this->logger);

        $this->pullTranslations($localEnvironment);
        $templatesFile = $this->pullTemplates($localEnvironment);
        $this->pullRoutes($localEnvironment, $templatesFile);
    }

    private function pullTranslations(LocalEnvironment $localEnvironment): void
    {
        $environment = $localEnvironment->getEnvironment();

        foreach ($this->builders->translation()->buildMessageCatalogues($environment) as $messageCatalogue) {
            $localEnvironment->dumpMessageCatalogue($messageCatalogue);
        }
    }

    private function pullTemplates(LocalEnvironment $localEnvironment): TemplatesFile
    {
        $templatesFile = new TemplatesFile();
        foreach ($this->builders->templating()->buildTemplates($localEnvironment->getEnvironment()) as $template) {
            $localEnvironment->dumpTemplate($template);
            $templatesFile->addTemplate($template);
        }

        $localEnvironment->dumpTemplatesFile($templatesFile);

        return $templatesFile;
    }

    private function pullRoutes(LocalEnvironment $localEnvironment, TemplatesFile $templatesFile): void
    {
        $routesFile = new RoutesFile($templatesFile);
        $routeConfigs = $this->builders->routing()->buildRouteConfigs($localEnvironment->getEnvironment());

        foreach ($routeConfigs as $routeConfig) {
            $routesFile->addRouteConfig($routeConfig);
        }

        $localEnvironment->dumpRoutesFile($routesFile);
    }
}
