<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Asset;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Storage\StorageManager;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Extension\RuntimeExtensionInterface;
use ZipArchive;

class AssetHelperRuntime implements RuntimeExtensionInterface
{
    /** @var StorageManager */
    private $storageManager;
    /** @var ClientRequestManager */
    private $manager;
    /** @var string */
    private $publicDir;
    /** @var Filesystem */
    private $filesystem;

    public function __construct(StorageManager $storageManager, ClientRequestManager $manager, string $projectDir)
    {
        $this->storageManager = $storageManager;
        $this->manager = $manager;
        $this->publicDir = $projectDir.'/public';

        $this->filesystem = new Filesystem();
    }

    public function assets(string $hash, string $saveDir = 'bundles'): void
    {
        $directory = $this->publicDir.'/'.$saveDir.'/'.$hash;

        try {
            $cacheKey = $this->manager->getDefault()->getCacheKey();
            $symlink = $this->publicDir.'/bundles/'.$cacheKey;

            if ($this->filesystem->exists($symlink.'/'.$hash)) {
                return; //valid
            }

            if (!$this->filesystem->exists($directory)) {
                $this->extract($this->storageManager->getStream($hash), $directory);
                $this->filesystem->touch($directory.'/'.$hash);
            }

            $this->manager->getLogger()->error('switching assets {symlink} to {hash}', ['symlink' => $symlink, 'hash' => $hash]);
            $this->filesystem->remove($symlink);
            $this->filesystem->symlink($directory, $symlink, true);
        } catch (\Exception $e) {
            $this->manager->getLogger()->error('emsch_assets failed : {error}', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function unzip(string $hash, string $saveDir): array
    {
        try {
            $this->extract($this->storageManager->getStream($hash), $saveDir);

            return \iterator_to_array(Finder::create()->in($saveDir)->files()->getIterator());
        } catch (\Exception $e) {
            $this->manager->getLogger()->error('emsch_assets failed : {error}', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return [];
    }

    private function extract(StreamInterface $stream, string $destination): bool
    {
        $path = \tempnam(\sys_get_temp_dir(), 'emsch');

        if (!$path) {
            throw new AssetException(\sprintf('Could not create temp file in %s', \sys_get_temp_dir()));
        }

        \file_put_contents($path, $stream->getContents());

        $zip = new ZipArchive();
        if (false === $open = $zip->open($path)) {
            throw new AssetException(\sprintf('Failed opening zip %s (ZipArchive %s)', $path, $open));
        }

        if (!$zip->extractTo($destination)) {
            throw new AssetException(\sprintf('Extracting of zip file failed (%s)', $destination));
        }

        $zip->close();

        return true;
    }
}
