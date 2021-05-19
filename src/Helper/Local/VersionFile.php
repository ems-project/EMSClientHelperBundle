<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use Symfony\Component\Filesystem\Filesystem;

final class VersionFile
{
    private ?string $version;
    private const NAME = '.version';

    public function __construct(string $directory)
    {
        $file = $directory.\DIRECTORY_SEPARATOR.self::NAME;
        $content = \file_exists($file) ? \file_get_contents($file) : false;
        $this->version = $content ?: null;
    }

    public static function build(string $directory, string $version): self
    {
        $filesystem = new Filesystem();
        $filesystem->dumpFile($directory.\DIRECTORY_SEPARATOR.self::NAME, $version);

        return new self($directory);
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }
}
