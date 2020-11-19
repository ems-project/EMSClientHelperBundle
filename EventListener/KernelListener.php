<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Request\ExceptionHelper;
use EMS\ClientHelperBundle\Helper\Request\LocaleHelper;
use EMS\ClientHelperBundle\Helper\Translation\TranslationHelper;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelListener implements EventSubscriberInterface
{
    /** @var EnvironmentHelper */
    private $environmentHelper;

    /** @var TranslationHelper */
    private $translationHelper;

    /** @var LocaleHelper */
    private $localeHelper;

    /** @var ExceptionHelper */
    private $exceptionHelper;

    /** @var bool */
    private $bindLocale;

    public function __construct(
        EnvironmentHelper $environmentHelper,
        TranslationHelper $translationHelper,
        LocaleHelper $localeHelper,
        ExceptionHelper $exceptionHelper,
        bool $bindLocale
    ) {
        $this->environmentHelper = $environmentHelper;
        $this->translationHelper = $translationHelper;
        $this->localeHelper = $localeHelper;
        $this->exceptionHelper = $exceptionHelper;
        $this->bindLocale = $bindLocale;
    }

    /**
     * @return array<string, array>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['bindEnvironment', 100],
                ['loadTranslations', 11],
            ],
            KernelEvents::RESPONSE => [
                ['bindLocale', 17],
            ],
            KernelEvents::EXCEPTION => [
                ['bindEnvironment', 100],
                ['redirectMissingLocale', 21],
                ['loadTranslations', 20], //not found is maybe redirected or custom error pages with translations
                ['customErrorTemplate', -10],
            ],
        ];
    }

    public function bindEnvironment(KernelEvent $event): void
    {
        $request = $event->getRequest();

        foreach ($this->environmentHelper->getEnvironments() as $env) {
            /** @var $env Environment */
            if ($env->matchRequest($request)) {
                $env->modifyRequest($request);
                break;
            }
        }
    }

    public function loadTranslations(KernelEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $this->translationHelper->addCatalogues();
        }
    }

    public function bindLocale(ResponseEvent $event): void
    {
        if ($this->bindLocale && $locale = $this->localeHelper->getLocale($event->getRequest())) {
            $event->getResponse()->headers->setCookie(new Cookie('_locale', $locale, \strtotime('now + 12 months')));
        }
    }

    public function redirectMissingLocale(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        if (!$this->bindLocale || !$event->isMasterRequest() || !$exception instanceof NotFoundHttpException) {
            return;
        }

        if (\preg_match('/(emsch_api_).*/', $request->attributes->get('_route'))) {
            return;
        }

        if (false === $this->localeHelper->getLocale($request)) {
            $event->setResponse($this->localeHelper->redirectMissingLocale($request));
        }
    }

    public function customErrorTemplate(ExceptionEvent $event): void
    {
        $flattenException = FlattenException::createFromThrowable($event->getThrowable());

        if ($template = $this->exceptionHelper->renderError($flattenException)) {
            $event->setResponse($template);
        }
    }
}
