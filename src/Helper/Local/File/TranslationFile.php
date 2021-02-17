<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local\File;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

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

    public function toArray(): array
    {
        return $this->flatten(Yaml::parseFile($this->resource, Yaml::PARSE_CONSTANT));
    }

    private function flatten(array $messages): array
    {
        $result = [];
        foreach ($messages as $key => $value) {
            if (\is_array($value)) {
                foreach ($this->flatten($value) as $k => $v) {
                    $result[$key.'.'.$k] = $v;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
