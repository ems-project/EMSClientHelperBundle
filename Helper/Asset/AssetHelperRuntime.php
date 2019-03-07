<?php

namespace EMS\ClientHelperBundle\Helper\Asset;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Storage\NotFoundException;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\RuntimeExtensionInterface;
use ZipArchive;

class AssetHelperRuntime implements RuntimeExtensionInterface
{
    /** @var StorageManager */
    private $storageManager;
    /** @var ClientRequestManager */
    private $manager;
    /** @var string */
    private $projectDir;
    /** @var Filesystem */
    private $filesystem;
    /** @var bool */
    private $enabled;

    public function __construct(StorageManager $storageManager, ClientRequestManager $manager, string $projectDir, bool $enabled)
    {
        $this->storageManager = $storageManager;
        $this->manager = $manager;
        $this->projectDir = $projectDir;
        $this->enabled = $enabled;

        $this->filesystem = new Filesystem();
    }

    /**
     * {{- emsch_assets('406210472030380156997695b489c479b926f695') -}}
     *
     * @param string $hash
     */
    public function dumpAssets(string $hash): void
    {
        if (!$this->enabled) {
            return;
        }

        $basePath = $this->projectDir . '/public/bundles';
        $directory = $basePath . '/' . $hash;

        try  {
            if (!$this->filesystem->exists($directory)) {
                $this->checkout($hash, $directory);
            }

            $cacheKey = $this->manager->getDefault()->getCacheKey();
            $symlink = sprintf('%s/%s', $basePath, $cacheKey);

            if (!$this->checkSign($symlink, $directory, $hash)) {
                $this->filesystem->remove($symlink);
                $this->filesystem->symlink($directory, $symlink, true);
            }
        } catch (\Exception $e) {
            $this->manager->getLogger()->error('emsch_assets failed : {error}', [
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'hash' => $hash,
                'directory' => $directory
            ]);
        }
    }

    private function checkout(string $hash, string $directory): void
    {
        try {
            $file = $this->storageManager->getFile($hash);
        } catch (NotFoundException $ex) {
            throw new AssetException(sprintf('Asset zip file not found with hash: ', $hash));
        }

        $this->extract($file, $directory);

        $this->filesystem->touch($directory . '/' . $hash);
    }

    private function extract(string $path, string $destination): bool
    {
        $zip = new ZipArchive;

        if (false === $open = $zip->open($path)) {
            throw new AssetException(sprintf('Failed opening zip %s (ZipArchive %s)', $path, $open));
        }

        if (!$zip->extractTo($destination)) {
            throw new AssetException(sprintf('Extracting of zip file failed (%s)', $path));
        }

        $zip->close();

        return true;
    }

    private function checkSign(string $symlink, string $directory, string $hash): bool
    {
        if (!$this->filesystem->exists($symlink)) {
            return false;
        }

        try {
            return sha1_file($symlink . '/' . $hash) === sha1_file($directory . '/' . $hash);
        } catch (\Exception $e) {
            return false;
        }
    }
}
