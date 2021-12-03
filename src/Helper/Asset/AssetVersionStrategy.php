<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Asset;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

final class AssetVersionStrategy implements VersionStrategyInterface
{
    private AssetHelperRuntime $assetHelperRuntime;

    public function __construct(AssetHelperRuntime $assetHelperRuntime)
    {
        $this->assetHelperRuntime = $assetHelperRuntime;
    }

    public function getVersion($path): string
    {
        return $this->assetHelperRuntime->getVersionHash();
    }

    public function applyVersion($path): string
    {
        return \sprintf('%s/%s/%s', $this->assetHelperRuntime->getVersionSaveDir(), $this->assetHelperRuntime->getVersionHash(), $path);
    }
}
