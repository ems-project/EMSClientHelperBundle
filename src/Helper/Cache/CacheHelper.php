<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Cache;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHelper
{
    private AdapterInterface $cache;
    private LoggerInterface $logger;
    private string $hashAlgo;

    public function __construct(AdapterInterface $cache, LoggerInterface $logger, string $hashAlgo)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->hashAlgo = $hashAlgo;
    }

    public function getContentType(ContentType $contentType): ?ContentType
    {
        $item = $this->cache->getItem($contentType->getCacheKey());

        if (!$item->isHit()) {
            return null;
        }

        $cachedContentType = $item->get();

        if (!$cachedContentType instanceof ContentType) {
            return null;
        }

        if ($cachedContentType->getCacheValidityTag() !== $contentType->getCacheValidityTag()) {
            $this->cache->deleteItem($contentType->getCacheKey());

            return null;
        }

        return $cachedContentType;
    }

    public function saveContentType(ContentType $contentType): void
    {
        $item = $this->cache->getItem($contentType->getCacheKey());

        if (!$item instanceof CacheItemInterface) {
            $this->logger->warning('Unexpected non-CacheItem cache item');

            return;
        }

        $item->set($contentType);
        $this->cache->save($item);
    }

    public function makeResponseCacheable(Request $request, Response $response): void
    {
        if (!\is_string($response->getContent())) {
            return;
        }

        $response->setCache([
            'etag' => \hash($this->hashAlgo, $response->getContent()),
            'max_age' => 600,
            's_maxage' => 3600,
            'public' => true,
            'private' => false,
        ]);
        $response->isNotModified($request);
    }
}
