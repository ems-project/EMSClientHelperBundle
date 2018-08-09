<?php

namespace EMS\ClientHelperBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use EMS\ClientHelperBundle\Service\ClearCacheService;

class ClearCacheRequestListener
{
    /**
     * @var ClearCacheService
     */
    private $clearCacheService;
    
    public function __construct(ClearCacheService $clearCacheService)
    {
        $this->clearCacheService = $clearCacheService;
    }
    
    /**
     * @param GetResponseEvent $event
     *
     * @return void
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->clearCacheService->isTranslationCacheFresh()) {
            $this->clearCacheService->clearTranslations();
        }
    }
}
