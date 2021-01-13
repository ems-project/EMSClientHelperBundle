<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Asset;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Storage\StorageManager;
use EMS\CommonBundle\Twig\AssetRuntime;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\RuntimeExtensionInterface;

class AssetHelperRuntime implements RuntimeExtensionInterface
{
    private StorageManager $storageManager;
    private ClientRequestManager $manager;
    private string $publicDir;
    private Filesystem $filesystem;

    public function __construct(StorageManager $storageManager, ClientRequestManager $manager, string $projectDir)
    {
        $this->storageManager = $storageManager;
        $this->manager = $manager;
        $this->publicDir = $projectDir.'/public';

        $this->filesystem = new Filesystem();
    }

    public function assets(string $hash, string $saveDir = 'bundles'): void
    {
        $basePath = $this->publicDir.\DIRECTORY_SEPARATOR.$saveDir.\DIRECTORY_SEPARATOR;
        $directory = $basePath.$hash;

        try {
            $cacheKey = $this->manager->getDefault()->getCacheKey();
            $symlink = $basePath.$cacheKey;

            if ($this->filesystem->exists($symlink.\DIRECTORY_SEPARATOR.$hash)) {
                return; //valid
            }

            if (!$this->filesystem->exists($directory)) {
                AssetRuntime::extract($this->storageManager->getStream($hash), $directory);
                $this->filesystem->touch($directory.\DIRECTORY_SEPARATOR.$hash);
            }

            $this->manager->getLogger()->warning('switching assets {symlink} to {hash}', ['symlink' => $symlink, 'hash' => $hash]);
            $this->filesystem->remove($symlink);
            $this->filesystem->symlink($directory, $symlink, true);
        } catch (\Exception $e) {
            $this->manager->getLogger()->error('emsch_assets failed : {error}', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }
}
