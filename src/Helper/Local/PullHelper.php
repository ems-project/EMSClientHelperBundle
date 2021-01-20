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
        $this->projectDir = $projectDir;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function pullTranslations(Environment $environment): void
    {
        $dumper = new YamlFileDumper('yaml');
        $path = $this->getPath($environment, ['translations']);

        foreach ($this->translationBuilder->buildMessageCatalogues($environment) as $messageCatalogue) {
            $dumper->dump($messageCatalogue, ['path' => $path, 'as_tree' => true, 'inline' => 5]);

            $this->logger->notice('Dumped translations {locale} in {path}', [
                'locale' => $messageCatalogue->getLocale(),
                'path' => $path,
            ]);
        }
    }

    public function pullRoutes(Environment $environment): void
    {
        $routes = $this->routingBuilder->buildRouteConfigs($environment);

        $fs = new Filesystem();
        $path = $this->getPath($environment, ['routes.json']);

        if (false !== $jsonRoutes = \json_encode($routes, JSON_PRETTY_PRINT)) {
            $fs->dumpFile($path, $jsonRoutes);
            $this->logger->notice('Dumped routes to {path}', ['path' => $path]);
        }
    }

    public function pullTemplates(Environment $environment): void
    {
        $fs = new Filesystem();

        foreach ($this->templatingBuilder->buildTemplates($environment) as $template) {
            $path = $this->getPath($environment, ['templates', $template->getName()]);
            $fs->dumpFile($path, $template->getCode());
        }
    }

    /**
     * @param string[] $append
     */
    private function getPath(Environment $environment, array $append = []): string
    {
        $path = \array_filter([$this->projectDir, 'local', $environment->getAlias()]);

        return \implode(DIRECTORY_SEPARATOR, \array_merge($path, $append));
    }
}
