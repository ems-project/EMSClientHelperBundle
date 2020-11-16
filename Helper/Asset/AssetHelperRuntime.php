<?php

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
        $this->publicDir = $projectDir . '/public';

        $this->filesystem = new Filesystem();
    }

    public function assets(string $hash, string $saveDir = 'bundles'): void
    {
        $directory = $this->publicDir . \DIRECTORY_SEPARATOR . $saveDir . \DIRECTORY_SEPARATOR . $hash;

        try {
            $cacheKey = $this->manager->getDefault()->getCacheKey();
            $symlink = $this->publicDir . '/bundles/' . $cacheKey;

            if ($this->filesystem->exists($symlink . \DIRECTORY_SEPARATOR . $hash)) {
                return; //valid
            }

            if (!$this->filesystem->exists($directory)) {
                $this->extract($this->storageManager->getStream($hash), $directory);
                $this->filesystem->touch($directory . \DIRECTORY_SEPARATOR . $hash);
            }

            $this->manager->getLogger()->error('switching assets {symlink} to {hash}', ['symlink' => $symlink, 'hash' => $hash]);
            $this->filesystem->remove($symlink);
            $this->filesystem->symlink($directory, $symlink, true);
        } catch (\Exception $e) {
            $this->manager->getLogger()->error('emsch_assets failed : {error}', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function unzip(string $hash, string $saveDir, bool $mergeContent = false): array
    {
        try {
            $checkFilename = $saveDir . \DIRECTORY_SEPARATOR . sha1($saveDir);
            $checkHash = file_exists($checkFilename) ? file_get_contents($checkFilename) : false;

            if ($checkHash !== $hash) {
                if (!$mergeContent && $this->filesystem->exists($saveDir)) {
                    $this->filesystem->remove($saveDir);
                }

                $this->extract($this->storageManager->getStream($hash), $saveDir);
                file_put_contents($checkFilename, $hash);
            }

            $excludeCheckFile = function (\SplFileInfo $f) use ($checkFilename) {
                return $f->getPathname() !== $checkFilename;
            };

            return iterator_to_array(Finder::create()->in($saveDir)->files()->filter($excludeCheckFile)->getIterator());
        } catch (\Exception $e) {
            $this->manager->getLogger()->error('emsch_assets failed : {error}', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return [];
    }

    private function extract(StreamInterface $stream, string $destination): bool
    {
        $path = tempnam(sys_get_temp_dir(), 'emsch');
        if (!$path) {
            throw new AssetException(sprintf('Could not create temp file in %s', sys_get_temp_dir()));
        }

        file_put_contents($path, $stream->getContents());

        $zip = new ZipArchive();
        if (false === $open = $zip->open($path)) {
            throw new AssetException(sprintf('Failed opening zip %s (ZipArchive %s)', $path, $open));
        }

        if (!$zip->extractTo($destination)) {
            throw new AssetException(sprintf('Extracting of zip file failed (%s)', $destination));
        }

        $zip->close();

        return true;
    }
}
