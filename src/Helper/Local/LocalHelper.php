<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use EMS\ClientHelperBundle\Helper\ContentType\ContentTypeHelper;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentApi;
use EMS\ClientHelperBundle\Helper\Local\Status\Status;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

final class LocalHelper
{
    private CacheItemPoolInterface $cache;
    private ContentTypeHelper $contentTypeHelper;
    private Builders $builders;
    private EnvironmentApi $environmentApi;
    private LoggerInterface $logger;

    public function __construct(
        CacheItemPoolInterface $cache,
        ContentTypeHelper $contentTypeHelper,
        Builders $builders,
        EnvironmentApi $environmentApi,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->contentTypeHelper = $contentTypeHelper;
        $this->builders = $builders;
        $this->environmentApi = $environmentApi;
        $this->logger = $logger;
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

    public function buildVersion(Environment $environment, bool $clearContentTypes = false): void
    {
        if ($clearContentTypes) {
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
        return $this->cache->getItem('token_'.\sha1($coreApi->getBaseUrl()));
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
            $mapping = $templates->getMapping($templateFile->getContentType());

            $status->addItemLocal($templateFile->getName(), $templateFile->getContentType(), [
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
}
