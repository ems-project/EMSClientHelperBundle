<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Routing\RoutingBuilder;
use EMS\ClientHelperBundle\Helper\Templating\TemplateBuilder;
use EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Dumper\YamlFileDumper;

final class PullHelper
{
    private LocalHelper $localHelper;
    private TemplateBuilder $templatingBuilder;
    private TranslationBuilder $translationBuilder;
    private RoutingBuilder $routingBuilder;
    private LoggerInterface $logger;
    private Filesystem $filesystem;

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
        $this->filesystem = new Filesystem();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function pull(Environment $environment): void
    {
        $this->pullTranslations($environment);

        $templateMapping = $this->pullTemplates($environment);

        $this->pullRoutes($environment, $templateMapping);
    }

    private function pullTranslations(Environment $environment): void
    {
        $dumper = new YamlFileDumper('yaml');
        $directory = $this->localHelper->getDirTranslations($environment);

        foreach ($this->translationBuilder->buildMessageCatalogues($environment) as $messageCatalogue) {
            $dumper->dump($messageCatalogue, ['path' => $directory, 'as_tree' => true, 'inline' => 5]);

            $this->logger->notice('Dumped translations {locale} in {path}', [
                'locale' => $messageCatalogue->getLocale(),
                'path' => $directory,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function pullTemplates(Environment $environment): array
    {
        $mapping = [];
        $directory = $this->localHelper->getDirTemplates($environment);

        foreach ($this->templatingBuilder->buildTemplates($environment) as $template) {
            $filePath = $directory.DIRECTORY_SEPARATOR.$template->getName();
            $this->filesystem->dumpFile($filePath, $template->getCode());
            $mapping[$template->getEmschNameId()] = $template->getName();
        }

        \asort($mapping);

        $fileTemplates = $this->localHelper->getFileTemplates($environment);
        $this->filesystem->dumpFile($fileTemplates, $this->jsonEncode($mapping));
        $this->logger->notice('Dumped templates to {file}', ['file' => $fileTemplates]);

        return $mapping;
    }

    /**
     * @param array<string, string> $templateMapping
     */
    private function pullRoutes(Environment $environment, array $templateMapping): void
    {
        $routes = [];
        $routeConfigs = $this->routingBuilder->buildRouteConfigs($environment);

        foreach ($routeConfigs as $routeConfig) {
            $route = $routeConfig->toArray();

            if (isset($route['template_static'])) {
                $route['template_static'] = $templateMapping[$route['template_static']] ?? $route['template_static'];
            }

            $routes[$routeConfig->getName()] = $route;
        }

        $fileRoutes = $this->localHelper->getFileRoutes($environment);
        $this->filesystem->dumpFile($fileRoutes, $this->jsonEncode($routes));
        $this->logger->notice('Dumped routes to {file}', ['file' => $fileRoutes]);
    }

    /**
     * @param array<mixed> $data
     */
    private function jsonEncode(array $data): string
    {
        $result = \json_encode($data, JSON_PRETTY_PRINT);

        if (false === $result) {
            throw new \RuntimeException('failed encoding json');
        }

        return $result;
    }
}
