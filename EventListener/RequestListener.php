<?php

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Request\RequestHelper;
use EMS\ClientHelperBundle\Helper\Translation\TranslationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestListener implements EventSubscriberInterface
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @var TranslationHelper
     */
    private $translationHelper;

    /**
     * @param RequestHelper     $requestHelper
     * @param TranslationHelper $translationHelper
     */
    public function __construct(RequestHelper $requestHelper, TranslationHelper $translationHelper)
    {
        $this->requestHelper = $requestHelper;
        $this->translationHelper = $translationHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['bindEnvironment', 100],
                ['loadTranslations', 15]
            ],
            KernelEvents::EXCEPTION => [
                ['bindEnvironment', 100]
            ]
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function bindEnvironment(GetResponseEvent $event)
    {
        $this->requestHelper->bindEnvironment($event->getRequest());
    }

    /**
     * @param GetResponseEvent $event
     */
    public function loadTranslations(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->translationHelper) {
            $this->translationHelper->addCatalogues($event->getRequest());
        }
    }
}
