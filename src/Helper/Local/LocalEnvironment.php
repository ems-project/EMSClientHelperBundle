<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Routing\RoutingFile;
use EMS\ClientHelperBundle\Helper\Templating\TemplateFiles;
use EMS\ClientHelperBundle\Helper\Translation\TranslationFiles;
use Symfony\Component\Filesystem\Filesystem;

final class LocalEnvironment
{
    private Environment $environment;
    private Filesystem $fileSystem;
    private string $directory;

    private ?RoutingFile $routingFile = null;
    private ?TemplateFiles $templatesFiles = null;
    private ?TranslationFiles $translationFiles = null;

    public function __construct(Environment $environment, string $path)
    {
        $this->environment = $environment;
        $this->directory = $path . \DIRECTORY_SEPARATOR . $environment->getName();
        $this->fileSystem = new Filesystem();
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getRouting(): RoutingFile
    {
        if (null === $this->routingFile) {
            $this->routingFile = new RoutingFile($this->directory);
        }

        return $this->routingFile;
    }

    public function getTemplates(): TemplateFiles
    {
        if (null === $this->templatesFiles) {
            $this->templatesFiles = new TemplateFiles($this->directory);
        }

        return $this->templatesFiles;
    }

    public function getTranslations(): TranslationFiles
    {
        if (null === $this->translationFiles) {
            $this->translationFiles = new TranslationFiles($this->directory);
        }

        return $this->translationFiles;
    }

    public function isPulled(): bool
    {
        return $this->fileSystem->exists($this->getDirectory());
    }

    public function loadFiles(): void
    {
        $this->routingFile = new RoutingFile($this->directory);
        $this->templatesFiles = new TemplateFiles($this->directory);
        $this->translationFiles = new TranslationFiles($this->directory);
    }
}
