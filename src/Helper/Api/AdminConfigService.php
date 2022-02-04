<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Api;

use EMS\CommonBundle\Common\Standard\Json;

final class AdminConfigService
{
    private string $saveFolder;

    public function __construct(string $saveFolder)
    {
        $this->saveFolder = $saveFolder;
    }

    /**
     * @param mixed[] $config
     */
    public function save(string $type, string $name, array $config): void
    {
        $directory = \implode(DIRECTORY_SEPARATOR, [$this->saveFolder, $type]);
        if (!\is_dir($directory)) {
            \mkdir($directory, 0777, true);
        }
        \file_put_contents($directory.DIRECTORY_SEPARATOR.$name.'.json', Json::encode($config, true));
    }
}
