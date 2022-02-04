<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Api;

use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class AdminConfigService
{
    private string $directory;
    private ConfigInterface $config;

    public function __construct(ConfigInterface $config, string $saveFolder)
    {
        $this->config = $config;
        $this->directory = \implode(DIRECTORY_SEPARATOR, [$saveFolder, $this->config->getType()]);
        if (!\is_dir($this->directory)) {
            \mkdir($this->directory, 0777, true);
        }
    }

    /**
     * @param mixed[] $config
     */
    public function save(string $name, array $config): void
    {
        \file_put_contents($this->directory.DIRECTORY_SEPARATOR.$name.'.json', Json::encode($config, true));
    }

    public function update(): void
    {
        $finder = new Finder();
        $jsonFiles = $finder->in($this->directory)->files()->name('*.json');
        foreach ($this->config->index() as $name => $config) {
            $jsonFiles->notName($name.'.json');
            $this->save($name, $config);
        }
        foreach ($jsonFiles as $file) {
            if (!$file instanceof SplFileInfo) {
                throw new \RuntimeException('Unexpected non SplFileInfo object');
            }
            \unlink($file->getPathname());
        }
    }
}
