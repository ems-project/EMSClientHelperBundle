<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\File;

final class TemplateFile
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function isFresh(int $time): bool
    {
        return \filemtime($this->path) < $time;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCode(): string
    {
        if (false === $content = \file_get_contents($this->path)) {
            throw new \RuntimeException(sprintf('Could not read template code in %s', $this->path));
        }

        return $content;
    }
}
