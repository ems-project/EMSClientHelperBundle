<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * @implements \IteratorAggregate<TemplateFile>
 */
final class TemplateFiles implements \IteratorAggregate, \Countable
{
    /** @var TemplateFile[] */
    private array $templateFiles = [];

    private const FILE_NAME = 'templates.yaml';

    public function __construct(string $directory)
    {
        $file = $directory.\DIRECTORY_SEPARATOR.self::FILE_NAME;
        $content = \file_exists($file) ? (\file_get_contents($file) ?: '') : '';
        $mapping = Yaml::parse($content);

        foreach ($mapping as $contentType => $templates) {
            foreach (Finder::create()->in($directory.\DIRECTORY_SEPARATOR.$contentType)->files() as $file) {
                $this->templateFiles[] = new TemplateFile($file, $contentType);
            }

            foreach ($templates as $ouuid => $name) {
                $this->getByName($contentType.'/'.$name)->setOuuid($ouuid);
            }
        }
    }

    /**
     * @param string[]           $contentTypes
     * @param TemplateDocument[] $documents
     */
    public static function build(string $directory, array $contentTypes, iterable $documents): self
    {
        $mapping = \array_map(fn () => [], \array_flip($contentTypes));
        $filesystem = new Filesystem();

        foreach ($documents as $document) {
            $filePath = [$directory, $document->getContentType(), $document->getName()];
            $filesystem->dumpFile(\implode(\DIRECTORY_SEPARATOR, $filePath), $document->getCode());

            $mapping[$document->getContentType()][$document->getId()] = $document->getName();
        }

        foreach ($mapping as $contentType => &$docs) {
            \asort($docs);
        }

        $jsonMapping = Yaml::dump($mapping);
        $filesystem->dumpFile($directory.\DIRECTORY_SEPARATOR.self::FILE_NAME, $jsonMapping);

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

    public function getByTemplateName(TemplateName $templateName): TemplateFile
    {
        return $this->getByName($templateName->getContentType().'/'.$templateName->getSearchValue());
    }

    public function getByName(string $name): TemplateFile
    {
        foreach ($this->templateFiles as $templateFile) {
            if ($name === $templateFile->getPathName()) {
                return $templateFile;
            }
        }

        throw new \RuntimeException(\sprintf('Could not find template "%s"', $name));
    }

    public function getByEmsName(string $emsName): TemplateFile
    {
        foreach ($this->templateFiles as $templateFile) {
            if ($emsName === $templateFile->getPathOuuid()) {
                return $templateFile;
            }
        }

        throw new \RuntimeException(\sprintf('Could not find template "%s"', $emsName));
    }
}
