<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Routing\RouteConfig;
use EMS\CommonBundle\Common\Json;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class LocalHelper
{
    private Filesystem $filesystem;
    private string $projectDir;

    public const TYPE_ROUTES = 'routes';
    public const TYPE_TRANSLATIONS = 'translations';
    public const TYPE_TEMPLATES = 'templates';
    private const WORKING_DIR = 'local';

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->filesystem = new Filesystem();
    }

    public function isLocal(Environment $environment): bool
    {
        return $this->filesystem->exists($this->getPath($environment));
    }

    /**
     * @return ?RouteConfig[]
     */
    public function getRouteConfigs(Environment $environment): ?array
    {
        $routeConfigs = [];
        $routingFile = $this->getFileRoutes($environment);

        if (!$this->filesystem->exists($routingFile)) {
            return $routeConfigs;
        }

        $decoded = Json::decode(\file_get_contents($routingFile));

        foreach ($decoded as $name => $options) {
            $routeConfigs[] = RouteConfig::fromArray($name, $options);
        }

        return $routeConfigs;
    }

    /**
     * @return TranslationFile[]
     */
    public function getTranslationFiles(Environment $environment): array
    {
        $files = [];
        $dirTranslations = $this->getDirTranslations($environment);

        if (!$this->filesystem->exists($dirTranslations)) {
            return $files;
        }

        foreach (Finder::create()->in($dirTranslations)->files()->name('*.yaml') as $file) {
            $files[] = new TranslationFile($file);
        }

        return $files;
    }

    public function getDirTranslations(Environment $environment): string
    {
        return $this->getPath($environment, ['translations']);
    }

    public function getDirTemplates(Environment $environment): string
    {
        return $this->getPath($environment, ['templates']);
    }

    public function getFileTemplates(Environment $environment): string
    {
        return $this->getPath($environment, ['templates.json']);
    }

    public function getFileRoutes(Environment $environment): string
    {
        return $this->getPath($environment, ['routes.json']);
    }

    /**
     * @param string[] $append
     */
    public function getPath(Environment $environment, array $append = []): string
    {
        $path = \array_filter([$this->projectDir, self::WORKING_DIR, $environment->getAlias()]);

        return \implode(DIRECTORY_SEPARATOR, \array_merge($path, $append));
    }
}
