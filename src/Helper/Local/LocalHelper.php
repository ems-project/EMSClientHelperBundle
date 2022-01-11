<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use EMS\ClientHelperBundle\Helper\ContentType\ContentTypeHelper;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentApi;
use EMS\ClientHelperBundle\Helper\Local\Status\Status;
use EMS\CommonBundle\Common\Standard\Hash;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

final class LocalHelper
{
    private CacheItemPoolInterface $cache;
    private ClientRequest $clientRequest;
    private ContentTypeHelper $contentTypeHelper;
    private Builders $builders;
    private EnvironmentApi $environmentApi;
    private LoggerInterface $logger;
    private string $projectDir;

    public function __construct(
        CacheItemPoolInterface $cache,
        ClientRequestManager $clientRequestManager,
        ContentTypeHelper $contentTypeHelper,
        Builders $builders,
        EnvironmentApi $environmentApi,
        LoggerInterface $logger,
        string $projectDir
    ) {
        $this->cache = $cache;
        $this->clientRequest = $clientRequestManager->getDefault();
        $this->contentTypeHelper = $contentTypeHelper;
        $this->builders = $builders;
        $this->environmentApi = $environmentApi;
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    public function api(Environment $environment): CoreApiInterface
    {
        $coreApi = $this->environmentApi->api($environment);
        $coreApi->setLogger($this->logger);

        $cacheToken = $this->apiCacheToken($coreApi);
        if ($cacheToken->isHit()) {
            $coreApi->setToken($cacheToken->get());
        }

        return $coreApi;
    }

    public function getUrl(): string
    {
        return $this->clientRequest->getUrl();
    }

    public function health(): string
    {
        return $this->clientRequest->healthStatus('green');
    }

    public function login(Environment $environment, string $username, string $password): CoreApiInterface
    {
        $coreApi = $this->environmentApi->login($environment, $username, $password);
        $coreApi->setLogger($this->logger);

        $this->cache->save($this->apiCacheToken($coreApi)->set($coreApi->getToken()));

        return $coreApi;
    }

    public function isUpToDate(Environment $environment): bool
    {
        $localVersion = $environment->getLocal()->getVersionFile()->getVersion();

        return $localVersion === $this->builders->getVersion($environment);
    }

    public function build(Environment $environment): void
    {
        $directory = $environment->getLocal()->getDirectory();

        $this->builders->build($environment, $directory);
        $environment->getLocal()->refresh();

        $this->buildVersion($environment);
    }

    public function buildVersion(Environment $environment, bool $refresh = false): void
    {
        if ($refresh) {
            if ('green' === $this->clientRequest->healthStatus('green')) {
                $this->clientRequest->refresh();
            }
            $this->contentTypeHelper->clear();
        }

        $directory = $environment->getLocal()->getDirectory();

        VersionFile::build($directory, $this->builders->getVersion($environment));
    }

    /**
     * @return Status[]
     */
    public function statuses(Environment $environment): array
    {
        return [
            $this->statusRouting($environment),
            $this->statusTemplating($environment),
            $this->statusTranslation($environment),
        ];
    }

    private function apiCacheToken(CoreApiInterface $coreApi): CacheItemInterface
    {
        return $this->cache->getItem(Hash::string($coreApi->getBaseUrl(), 'token_'));
    }

    private function statusRouting(Environment $environment): Status
    {
        $status = new Status('Routing');
        $status->addBuilderDocuments($this->builders->routing()->getDocuments($environment));

        if (null === $contentType = $this->builders->routing()->getContentType($environment)) {
            return $status;
        }

        foreach ($environment->getLocal()->getRouting()->getData() as $name => $data) {
            $status->addItemLocal($name, $contentType->getName(), $data);
        }

        return $status;
    }

    private function statusTemplating(Environment $environment): Status
    {
        $status = new Status('Templating');
        $status->addBuilderDocuments($this->builders->templating()->getDocuments($environment));

        $templates = $this->builders->templating()->getTemplates($environment);

        foreach ($environment->getLocal()->getTemplates() as $templateFile) {
            $mapping = $templates->getMapping($templateFile->getContentTypeName());

            $status->addItemLocal($templateFile->getName(), $templateFile->getContentTypeName(), [
                ($mapping['name']) => $templateFile->getName(),
                ($mapping['code']) => $templateFile->getCode(),
            ]);
        }

        return $status;
    }

    private function statusTranslation(Environment $environment): Status
    {
        $status = new Status('Translations');
        $status->addBuilderDocuments($this->builders->translation()->getDocuments($environment));

        if (null === $contentType = $this->builders->translation()->getContentType($environment)) {
            return $status;
        }

        foreach ($environment->getLocal()->getTranslations()->getData() as $name => $data) {
            $status->addItemLocal($name, $contentType->getName(), $data);
        }

        return $status;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function makeAssetsArchives(string $baseUrl): string
    {
        $directory = \implode(DIRECTORY_SEPARATOR, [$this->projectDir, 'public', $baseUrl]);
        if (!\is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Directory not found %s', $baseUrl));
        }

        $zipFile = \tempnam(\sys_get_temp_dir(), 'zip');
        if (!\is_string($zipFile)) {
            throw new \RuntimeException('Error while generating a temporary zip file');
        }

        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::OVERWRITE);

        $finder = new Finder();
        $finder->files()->in($directory);

        if (!$finder->hasResults()) {
            throw new \RuntimeException('The directory is empty');
        }

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();
            $filename = $file->getRelativePathname();
            if (!\is_string($filePath)) {
                throw new \RuntimeException(\sprintf('File %s path not found', $filename));
            }
            $zip->addFile($filePath, $filename);
        }

        $zip->addPattern('/.*/', $directory);
        $zip->close();

        return $zipFile;
    }
}
