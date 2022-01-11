<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Helper\Local\ConfigFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @implements \IteratorAggregate<TemplateFile>
 */
final class TemplateFiles implements \IteratorAggregate, \Countable
{
    /** @var TemplateFile[] */
    private array $templateFiles = [];

    public function __construct(string $directory)
    {
        $config = ConfigFile::fromDir($directory);

        foreach ($config->getTemplateContentTypeNames() as $templateContentTypeName) {
            $path = $directory.\DIRECTORY_SEPARATOR.$templateContentTypeName;

            foreach (Finder::create()->in($path)->files() as $file) {
                $this->templateFiles[] = new TemplateFile($file, $templateContentTypeName);
            }
        }
    }

    /**
     * @param TemplateDocument[] $documents
     */
    public static function build(string $directory, Templates $templates, iterable $documents): self
    {
        $filesystem = new Filesystem();

        foreach ($documents as $document) {
            $filePath = [$directory, $document->getContentType(), $document->getName()];
            $filesystem->dumpFile(\implode(\DIRECTORY_SEPARATOR, $filePath), $document->getCode());
        }

        ConfigFile::fromDir($directory)->addTemplates($templates)->save();

        return new self($directory);
    }

    public function count(): int
    {
        return \count($this->templateFiles);
    }

    /**
     * @return \ArrayIterator<int, TemplateFile>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->templateFiles);
    }

    public function get(string $search): TemplateFile
    {
        if (null === $template = $this->find($search)) {
            throw new \RuntimeException(\sprintf('Could not find template "%s"', $search));
        }

        return $template;
    }

    public function getByTemplateName(TemplateName $templateName): TemplateFile
    {
        return $this->get($templateName->getSearchName());
    }

    public function find(string $search): ?TemplateFile
    {
        foreach ($this->templateFiles as $templateFile) {
            if ($search === $templateFile->getPathOuuid() || $search === $templateFile->getPathName()) {
                return $templateFile;
            }
        }

        return null;
    }
}
