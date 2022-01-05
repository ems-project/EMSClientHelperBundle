<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Controller\CacheController;
use EMS\ClientHelperBundle\Helper\Request\RequestHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class CacheListener implements EventSubscriberInterface
{
    private CacheController $cacheController;
    private LoggerInterface $logger;

    public function __construct( CacheController $cacheController, LoggerInterface $logger)
    {
        $this->cacheController = $cacheController;
        $this->logger = $logger;
    }


    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['cacheRequest', 300]
            ],
            KernelEvents::TERMINATE => [
                ['terminate', 300]
            ]
        ];
    }

    public function cacheRequest(ControllerEvent $event)
    {
        if (null !== $this->getCacheKey($event->getRequest())) {
            $event->setController($this->cacheController);
        }
    }

    public function terminate(TerminateEvent $event)
    {
        if (null === $cacheKey = $this->getCacheKey($event->getRequest())) {
            return;
        }

        \fastcgi_finish_request();
        \set_time_limit(0);

        sleep(5);

        $this->logger->info('OKAAAAAAAAAAAAAAAAAAAAAY');

    }

    private function getCacheKey(Request $request): ?string
    {
        $emschCacheKey = $request->attributes->get('emsch_cache_key', false);

        return $emschCacheKey ? RequestHelper::replace($request, $emschCacheKey) : null;
    }
}