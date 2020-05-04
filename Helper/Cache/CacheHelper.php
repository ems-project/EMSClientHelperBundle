<?php

namespace EMS\ClientHelperBundle\Helper\Cache;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHelper
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    const DATE_KEY = 'cache_date';

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(string $key): ?CacheItemInterface
    {
        return $this->cache->getItem($key);
    }

    public function isValid(CacheItem $item, \DateTime $lastChanged): bool
    {
        if (!$item->isHit()) {
            return false;
        }

        $data = $item->get();
        $cacheDate = \DateTime::createFromFormat(DATE_ATOM, $data[self::DATE_KEY]);

        return $cacheDate > $lastChanged;
    }

    public function getData(CacheItem $item): array
    {
        return $item->get()['data'];
    }

    public function save(CacheItem $item, array $data)
    {
        $now = new \DateTime();

        $item->set([
            self::DATE_KEY => $now->format(DATE_ATOM),
            'data' => $data
        ]);

        $this->cache->save($item);
    }

    public static function makeResponseCacheable(Request $request, Response $response): void
    {
        if (!is_string($response->getContent())) {
            return;
        }

        $response->setCache([
            'etag' => \md5($response->getContent()),
            'max_age' => 600,
            's_maxage' => 3600,
            'public' => true,
            'private' => false,
        ]);
        $response->isNotModified($request);
    }
}
