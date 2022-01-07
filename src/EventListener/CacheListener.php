<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Controller\CacheController;
use EMS\ClientHelperBundle\Helper\Cache\CacheResponse;
use EMS\ClientHelperBundle\Helper\Request\EmschRequest;
use EMS\CommonBundle\Contracts\Elasticsearch\QueryLoggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

final class CacheListener implements EventSubscriberInterface
{
    private CacheController $cacheController;
    private Kernel $kernel;
    private LoggerInterface $logger;
    private QueryLoggerInterface $queryLogger;

    public function __construct(
        CacheController $cacheController,
        Kernel $kernel,
        LoggerInterface $logger,
        QueryLoggerInterface $queryLogger
    ) {
        $this->cacheController = $cacheController;
        $this->kernel = $kernel;
        $this->logger = $logger;
        $this->queryLogger = $queryLogger;
    }

    /**
     * @return array<mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                ['cacheRequest', 300],
            ],
            KernelEvents::TERMINATE => [
                ['terminate', 300],
            ],
        ];
    }

    public function cacheRequest(ControllerEvent $event): void
    {
        if (EmschRequest::fromRequest($event->getRequest())->hasEmschCache()) {
            $this->logger->debug('Changing controller for checking cache');
            $event->setController($this->cacheController);
        }
    }

    public function terminate(TerminateEvent $event): void
    {
        $response = $event->getResponse();
        $emschRequest = EmschRequest::fromRequest($event->getRequest());

        if (!$emschRequest->hasEmschCache() || $response->headers->has(CacheResponse::HEADER_X_EMSCH_CACHE)) {
            return;
        }

        $emschCacheKey = $emschRequest->getEmschCacheKey();
        $this->logger->debug(\sprintf('Starting sub request for %s', $emschCacheKey));

        $emschRequest->closeSession();

        $subRequest = EmschRequest::fromRequest($emschRequest->duplicate());
        $subRequest->makeSubRequest();

        \set_time_limit($emschRequest->getEmschCacheLimit());
        $this->queryLogger->disable();
        $response = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

        $cacheHelper = $this->cacheController->getCacheHelper();
        $cacheHelper->saveResponse($response, $emschCacheKey);

        $this->logger->debug(\sprintf('Finished sub request for %s', $emschCacheKey));
    }
}
