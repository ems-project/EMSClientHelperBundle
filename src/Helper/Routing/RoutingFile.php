<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\ClientHelperBundle\Helper\Templating\TemplateFiles;
use EMS\CommonBundle\Common\Standard\Json;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class RoutingFile implements \Countable
{
    private TemplateFiles $templateFiles;
    /** @var array<string, mixed> */
    private array $routes = [];

    private const FILE_NAME = 'routes.yaml';

    public function __construct(string $directory)
    {
        $file = $directory.\DIRECTORY_SEPARATOR.self::FILE_NAME;
        $content = \file_exists($file) ? (\file_get_contents($file) ?: '') : '';
        $routes = \file_exists($file) ? Yaml::parse($content) : [];

        $this->templateFiles = new TemplateFiles($directory);

        foreach ($routes as $name => $data) {
            if (isset($data['config'])) {
                $data['config'] = Json::encode($data['config']);
            }

            $data['name'] = $name;
            $this->routes[$name] = $data;
        }
    }

    /**
     * @param RoutingDocument[] $documents
     */
    public static function build(string $directory, iterable $documents): self
    {
        $routes = [];
        $templatesFile = new TemplateFiles($directory);

        foreach ($documents as $document) {
            $data = $document->getDataSource();
            if (isset($data['template_static'])) {
                $templateFile = $templatesFile->find($data['template_static']);
                $data['template_static'] = $templateFile ? $templateFile->getPathName() : $data['template_static'];
            }

            if (isset($data['config'])) {
                $data['config'] = Json::decode($data['config']);
            }

            unset($data['name']);
            $routes[$document->getName()] = $data;
        }

        $fileName = $directory.\DIRECTORY_SEPARATOR.self::FILE_NAME;
        $fs = new Filesystem();
        $fs->dumpFile($fileName, Yaml::dump($routes, 3));

        return new self($directory);
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        $data = [];

        foreach ($this->routes as $name => $route) {
            if (isset($route['template_static'])) {
                $template = $this->templateFiles->find($route['template_static']);
                if ($template) {
                    $route['template_static'] = $template->getPathOuuid();
                }
            }

            $data[$name] = $route;
        }

        return $data;
    }

    public function count(): int
    {
        return \count($this->routes);
    }

    /**
     * @return \Generator|Route[]
     */
    public function createRoutes(): \Generator
    {
        foreach ($this->routes as $name => $data) {
            if (isset($data['template_static'])) {
                $template = $this->templateFiles->find($data['template_static']);
                $data['template_static'] = $template ? $template->getPathName() : $data['template_static'];
            }

            yield Route::fromData($name, $data);
        }
    }
}
