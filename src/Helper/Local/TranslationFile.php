<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use Symfony\Component\Finder\SplFileInfo;

final class TranslationFile
{
    public string $resource;
    public string $format;
    public string $locale;
    public string $domain;

    public function __construct(SplFileInfo $file)
    {
        /** @var array{string, string, string} $fileNameParts */
        $fileNameParts = \explode('.', $file->getFilename());

        $this->format = \array_pop($fileNameParts);
        $this->locale = \array_pop($fileNameParts);
        $this->domain = \implode('.', $fileNameParts);
        $this->resource = $file->getPathname();
    }
}
