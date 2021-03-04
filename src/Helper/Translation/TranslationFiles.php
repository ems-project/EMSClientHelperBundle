<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Translation;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Dumper\YamlFileDumper;
use Symfony\Component\Translation\MessageCatalogue;

final class TranslationFiles implements \IteratorAggregate
{
    /** @var TranslationFile[] */
    private array $files = [];

    private const DIRECTORY = 'translations';

    public function __construct(string $directory)
    {
        $path = $directory . \DIRECTORY_SEPARATOR . self::DIRECTORY;

        if (\file_exists($path)) {
            foreach (Finder::create()->in($path)->files()->name('*.yaml') as $file) {
                $this->files[] = new TranslationFile($file);
            }
        }
    }

    /**
     * @param MessageCatalogue[] $messageCatalogues
     */
    public static function build(string $directory, iterable $messageCatalogues): self
    {
        $path = $directory . \DIRECTORY_SEPARATOR . self::DIRECTORY;
        $dumper = new YamlFileDumper('yaml');

        foreach ($messageCatalogues as $messageCatalogue) {
            $dumper->dump($messageCatalogue, ['path' => $path, 'as_tree' => true, 'inline' => 5]);
        }

        return new self($directory);
    }

    /**
     * @return \ArrayIterator|TranslationFile[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->files);
    }

    public function getData(): array
    {
        $result = [];

        foreach ($this->files as $file) {
            foreach ($file->toArray() as $key => $label) {
                $result[$key]['label_'.$file->locale] = $label;
            }
        }

        return $result;
    }
}