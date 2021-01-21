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
    private TemplateBuilder $templatingBuilder;
    private TranslationBuilder $translationBuilder;
    private RoutingBuilder $routingBuilder;
    private LoggerInterface $logger;
    private Filesystem $filesystem;
    private string $projectDir;

    public function __construct(
        TemplateBuilder $templatingBuilder,
        TranslationBuilder $translationBuilder,
        RoutingBuilder $routingBuilder,
        LoggerInterface $logger,
        string $projectDir
    ) {
        $this->templatingBuilder = $templatingBuilder;
        $this->translationBuilder = $translationBuilder;
        $this->routingBuilder = $routingBuilder;
        $this->logger = $logger;
        $this->filesystem = new Filesystem();
        $this->projectDir = $projectDir;
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
        $path = $this->getFilePath($environment, ['translations']);

        foreach ($this->translationBuilder->buildMessageCatalogues($environment) as $messageCatalogue) {
            $dumper->dump($messageCatalogue, ['path' => $path, 'as_tree' => true, 'inline' => 5]);

            $this->logger->notice('Dumped translations {locale} in {path}', [
                'locale' => $messageCatalogue->getLocale(),
                'path' => $path,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function pullTemplates(Environment $environment): array
    {
        $mapping = [];

        foreach ($this->templatingBuilder->buildTemplates($environment) as $template) {
            $filePath = $this->getFilePath($environment, ['templates', $template->getName()]);
            $this->filesystem->dumpFile($filePath, $template->getCode());
            $mapping[$template->getEmschNameId()] = $template->getName();
        }

        \asort($mapping);
        $this->filesystem->dumpFile($this->getFilePath($environment, ['templates.json']), $this->jsonEncode($mapping));

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

        $path = $this->getFilePath($environment, ['routes.json']);
        $this->filesystem->dumpFile($path, $this->jsonEncode($routes));
        $this->logger->notice('Dumped routes to {path}', ['path' => $path]);
    }

    /**
     * @param string[] $append
     */
    private function getFilePath(Environment $environment, array $append = []): string
    {
        $path = \array_filter([$this->projectDir, 'local', $environment->getAlias()]);

        return \implode(DIRECTORY_SEPARATOR, \array_merge($path, $append));
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
