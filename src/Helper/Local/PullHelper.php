<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Routing\RoutingBuilder;
use EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Dumper\YamlFileDumper;

final class PullHelper
{
    private TranslationBuilder $translationBuilder;
    private RoutingBuilder $routingBuilder;
    private string $projectDir;

    public function __construct(
        TranslationBuilder $translationBuilder,
        RoutingBuilder $routingBuilder,
        string $projectDir
    ) {
        $this->translationBuilder = $translationBuilder;
        $this->routingBuilder = $routingBuilder;
        $this->projectDir = $projectDir;
    }

    public function pullTranslations(Environment $environment): void
    {
        $dumper = new YamlFileDumper('yaml');
        $path = $this->getPath($environment, 'translations');

        foreach ($this->translationBuilder->buildMessageCatalogues($environment) as $messageCatalogue) {
            $dumper->dump($messageCatalogue, ['path' => $path, 'as_tree' => true, 'inline' => 5]);
        }
    }

    public function pullRoutes(Environment $environment): void
    {
        $routes = $this->routingBuilder->buildRouteConfigs($environment);

        $fs = new Filesystem();
        $path = $this->getPath($environment, 'routes.json');

        if (false !== $jsonRoutes = \json_encode($routes, JSON_PRETTY_PRINT)) {
            $fs->dumpFile($path, $jsonRoutes);
        }
    }

    private function getPath(Environment $environment, ?string $suffix = null): string
    {
        $path = \array_filter([$this->projectDir, 'local', $environment->getAlias(), $suffix]);

        return \implode(DIRECTORY_SEPARATOR, $path);
    }
}
