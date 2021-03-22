<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use Symfony\Component\Finder\SplFileInfo;

final class TemplateFile
{
    private string $name;
    private ?string $ouuid = null;
    private string $path;
    private string $contentType;

    public function __construct(SplFileInfo $file, string $contentType)
    {
        $this->path = $file->getPathname();
        $this->contentType = $contentType;

        $pathName = $file->getRelativePathname();
        if ('/' !== \DIRECTORY_SEPARATOR) {
            $pathName = \str_replace(\DIRECTORY_SEPARATOR, '/', $pathName);
        }

        $this->name = $pathName;
    }

    public function hasOuuid(): bool
    {
        return null !== $this->ouuid;
    }

    public function getCode(): string
    {
        if (false === $content = \file_get_contents($this->path)) {
            throw new \RuntimeException(\sprintf('Could not read template code in %s', $this->path));
        }

        return $content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPathName(): string
    {
        return $this->contentType.'/'.$this->name;
    }

    public function getPathOuuid(): string
    {
        return $this->contentType.':'.$this->ouuid;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isFresh(int $time): bool
    {
        return \filemtime($this->path) < $time;
    }

    public function setOuuid(?string $ouuid): void
    {
        $this->ouuid = $ouuid;
    }
}
