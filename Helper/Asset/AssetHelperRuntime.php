<?php

namespace EMS\ClientHelperBundle\Helper\Asset;

use EMS\ClientHelperBundle\Helper\Request\RequestHelper;
use EMS\CommonBundle\Storage\NotFoundException;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\RuntimeExtensionInterface;
use ZipArchive;

class AssetHelperRuntime implements RuntimeExtensionInterface
{
    /**
     * @var StorageManager
     */
    private $storageManager;

    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    const SIGN_FILE = 'ems_sign';

    /**
     * @param StorageManager $storageManager
     * @param RequestHelper  $requestHelper
     * @param string         $projectDir
     */
    public function __construct(StorageManager $storageManager, RequestHelper $requestHelper, string $projectDir)
    {
        $this->storageManager = $storageManager;
        $this->requestHelper = $requestHelper;
        $this->projectDir = $projectDir;

        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $hash
     */
    public function init(string $hash)
    {
        $basePath = $this->projectDir . '/public/bundles';
        $directory = $basePath . '/' . $hash;

        if (!$this->filesystem->exists($directory)) {
            $this->checkout($hash, $directory);
        }

        $env = $this->requestHelper->getEnvironment();
        $symlink = sprintf('%s/%s', $basePath, $env);

        if (!$this->checkSign($symlink, $directory, $hash)) {
            $this->filesystem->remove($symlink);
            $this->filesystem->symlink($directory, $symlink, true);
        }
    }

    /**
     * @param string $hash
     * @param string $directory
     */
    private function checkout(string $hash, string $directory)
    {
        try {
            $file = $this->storageManager->getFile($hash);
        } catch (NotFoundException $ex) {
            throw new AssetException(sprintf('Asset zip file not found with hash: ', $hash));
        }

        $this->extract($file, $directory);

        $this->filesystem->touch($directory . '/' . $hash);
    }

    /**
     * @param string $path
     * @param string $destination
     *
     * @return bool
     */
    private function extract(string $path, string $destination)
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

    /**
     * @param $symlink
     * @param $directory
     *
     * @param $hash
     * @return bool
     */
    private function checkSign($symlink, $directory, $hash)
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
