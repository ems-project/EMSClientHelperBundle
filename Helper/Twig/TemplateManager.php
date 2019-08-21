<?php

namespace EMS\ClientHelperBundle\Helper\Twig;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Component\Filesystem\Filesystem;

class TemplateManager
{
    /** @var ClientRequestManager */
    private $clientRequestManager;
    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $path;

    public function __construct(ClientRequestManager $clientRequestManager, string $path)
    {
        $this->clientRequestManager = $clientRequestManager;
        $this->filesystem = new Filesystem();
        $this->path = $path;
    }

    public function getCode(Template $template): string
    {
        $info = $this->getTemplateInfo($template);

        if (null !== $info) {
            return file_get_contents($this->path . '/' . $template->getCacheKey() . '/' . $template->getContentType() . '/' . $info['name']);
        }

        return $template->getCode();
    }

    public function isDownloaded(Template $template): bool
    {
        $info = $this->getTemplateInfo($template);

        return $info !== null;
    }

    private function getTemplateInfo(Template $template): ?array
    {
        $manifest = $this->getManifest($template->getCacheKey());

        if (null === $manifest) {
            return null;
        }

        $info = $manifest['templates'][$template->getContentType()] ?? [];

        if (null === $info) {
            return null;
        }

        if ($template->hasOuuid()) {
            return $info[$template->getOuuid()] ?? null;
        }

        foreach ($info as $record) {
            if ($record['name'] === $template->getName()) {
                return $record;
            }
        }

        return null;
    }

    private function getManifest(?string $cacheKey): ?array
    {
        $path = $this->path . '/' . $cacheKey . '/manifest.json';

        if ($this->filesystem->exists($path)) {
            return \json_decode(file_get_contents($path), true);
        }

        return null;
    }

    public function download(): \Generator
    {
        $clientRequest = $this->clientRequestManager->getDefault();

        $dir = $this->path . '/' . $clientRequest->getCacheKey();
        $templates = $clientRequest->getOption('[templates]');

        $manifest = ['last_download' => date(' d/m/Y H:i:s'), 'templates' => []];

        $this->filesystem->mkdir($dir);

        foreach ($templates as $contentType => $mapping) {
            list($fieldName, $fieldTwig) = array_values($mapping);
            $manifest['templates'][$contentType] = [];

            $params = ['body' => [
                'query' => ['term' => ['_contenttype' => $contentType ]],
                'sort' => [ [$fieldName => ['order' => 'asc']] ]
            ]];

            foreach ($clientRequest->scrollAll($params) as $hit) {
                $source = $hit['_source'];
                $this->filesystem->dumpFile($dir . '/' . $contentType . '/' . $source[$fieldName], $source[$fieldTwig]);

                $manifest['templates'][$contentType][$hit['_id']] = ['name' => $source[$fieldName]];

                yield $source[$fieldName];
            }
        }

        $this->filesystem->dumpFile($dir . '/manifest.json', \json_encode($manifest));
    }
}
