<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Local\File\TranslationFile;
use EMS\ClientHelperBundle\Helper\Routing\RouteConfig;
use EMS\ClientHelperBundle\Helper\Templating\Template;
use EMS\CommonBundle\Common\Json;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\MessageCatalogue;

final class LocalEnvironment
{
    private Environment $environment;
    private Filesystem $filesystem;
    private LoggerInterface $logger;
    private string $path;

    private const FILE_ROUTES = 'routes.json';
    private const FILE_TEMPLATES = 'templates.json';

    private const DIR_TRANSLATIONS = 'translations';
    private const DIR_TEMPLATES = 'templates';

    public function __construct(Environment $environment, LoggerInterface $logger, string $path)
    {
        $this->environment = $environment;
        $this->path = $path;
        $this->logger = $logger;
        $this->filesystem = new Filesystem();
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function isPulled(): bool
    {
        return $this->filesystem->exists($this->path());
    }

    /**
     * @return RouteConfig[]
     */
    public function getRouteConfigs(): array
    {
        $routeConfigs = [];
        $routingFile = $this->path(self::FILE_ROUTES);

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
    public function getTranslationFiles(): array
    {
        $files = [];
        $dirTranslations = $this->path(self::DIR_TRANSLATIONS);

        if (!$this->filesystem->exists($dirTranslations)) {
            return $files;
        }

        foreach (Finder::create()->in($dirTranslations)->files()->name('*.yaml') as $file) {
            $files[] = new TranslationFile($file);
        }

        return $files;
    }

    public function dumpMessageCatalogue(MessageCatalogue $messageCatalogue): void
    {
        $directory = $this->path(self::DIR_TRANSLATIONS);
        $dumper = new YamlFileDumper('yaml');
        $dumper->dump($messageCatalogue, ['path' => $directory, 'as_tree' => true, 'inline' => 5]);

        $this->logger->notice('Dumped translations {locale} in {path}', [
            'locale' => $messageCatalogue->getLocale(),
            'path' => $directory,
        ]);
    }

    public function dumpTemplate(Template $template): void
    {
        $dirTemplates = $this->path(self::DIR_TEMPLATES);
        $filePath = $dirTemplates.DIRECTORY_SEPARATOR.$template->getName();

        $this->filesystem->dumpFile($filePath, $template->getCode());
    }

    /**
     * @param array<string, string> $templates
     */
    public function dumpJsonTemplates(array $templates): void
    {
        \asort($templates);

        $filePath = $this->path(self::FILE_TEMPLATES);
        $this->filesystem->dumpFile($filePath, Json::encode($templates, true));
        $this->logger->notice('Dumped templates to {file}', ['file' => $filePath]);
    }

    /**
     * @param array<string, array<mixed>> $routes
     */
    public function dumpJsonRoutes(array $routes): void
    {
        $filePath = $this->path(self::FILE_ROUTES);
        $this->filesystem->dumpFile($filePath, Json::encode($routes, true));
        $this->logger->notice('Dumped routes to {file}', ['file' => $filePath]);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
    
    private function path(string ...$location): string
    {
        $basePath = [$this->path, $this->environment->getAlias()];

        return \implode(DIRECTORY_SEPARATOR, \array_merge($basePath, $location));
    }
}
