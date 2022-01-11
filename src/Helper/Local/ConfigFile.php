<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Templating\Templates;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class ConfigFile
{
    private string $file;
    /** @var array<mixed> */
    private array $config;
    private const FILENAME = 'config.yaml';

    private function __construct(string $directory)
    {
        $file = $directory.\DIRECTORY_SEPARATOR.self::FILENAME;
        $content = \file_exists($file) ? (\file_get_contents($file) ?: '') : '';

        $this->file = $file;
        $this->config = Yaml::parse($content) ?? [];
    }

    public function isEmpty(): bool
    {
        return [] === $this->config;
    }

    public static function fromDir(string $directory): self
    {
        return new self($directory);
    }

    /**
     * @return string[]
     */
    public function getTemplateContentTypeNames(): array
    {
        return $this->config['config']['template_content_types'] ?? [];
    }

    public function addTemplates(Templates $templates): self
    {
        $contentTypes = $templates->getContentTypes();
        $contentTypeNames = \array_map(fn (ContentType $contentType) => $contentType->getName(), $contentTypes);
        $this->config['config']['template_content_types'] = $contentTypeNames;

        return $this;
    }

    public function save(): void
    {
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->file, Yaml::dump($this->config));
    }
}
