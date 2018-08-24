<?php

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Request\LocaleHelper;
use EMS\ClientHelperBundle\Helper\Request\RequestHelper;
use EMS\ClientHelperBundle\Helper\Translation\TranslationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelListener implements EventSubscriberInterface
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
     * @var LocaleHelper
     */
    private $localeHelper;

    /**
     * @param RequestHelper     $requestHelper
     * @param TranslationHelper $translationHelper
     * @param LocaleHelper      $localeHelper
     */
    public function __construct(
        RequestHelper $requestHelper,
        TranslationHelper $translationHelper,
        LocaleHelper $localeHelper
    )
    {
        $this->requestHelper = $requestHelper;
        $this->translationHelper = $translationHelper;
        $this->localeHelper = $localeHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['bindEnvironment', 100],
                ['bindLocale', 17],
                ['loadTranslations', 11],
            ],
            KernelEvents::EXCEPTION => [
                ['bindEnvironment', 100],
                ['redirectMissingLocale', 21],
                ['loadTranslations', 20] //not found is maybe redirected or custom error pages with translations
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
        if ($event->isMasterRequest()) {
            $this->translationHelper->addCatalogues($event->getRequest());
        }
    }

    /**
     * @param GetResponseEvent $event
     */
    public function bindLocale(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($locale = $this->localeHelper->getLocale($request)) {
            $request->getSession()->set('_locale', $locale);
        }
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function redirectMissingLocale(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        $exception = $event->getException();

        if (!$event->isMasterRequest() || !$exception instanceof NotFoundHttpException) {
            return;
        }

        if (preg_match('/(emsch_api_).*/', $request->attributes->get('_route'))) {
            return;
        }

        if (false === $this->localeHelper->getLocale($request)) {
            $event->setResponse($this->localeHelper->redirectMissingLocale($request));
        }
    }
}
