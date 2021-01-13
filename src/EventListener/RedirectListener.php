<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Routing\RedirectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class RedirectListener implements EventSubscriberInterface
{
    private RedirectHelper $redirectHelper;
    private HttpKernelInterface $kernel;
    private RouterInterface $router;

    public function __construct(
        RedirectHelper $redirectHelper,
        HttpKernelInterface $kernel,
        RouterInterface $router
    ) {
        $this->redirectHelper = $redirectHelper;
        $this->kernel = $kernel;
        $this->router = $router;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundHttpException) {
            $this->handleNotFoundException($event);

            return;
        }
    }

    /**
     * @return array<string, array<int, array<int, int|string>>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [['onKernelException', 15]],
        ];
    }

    private function handleNotFoundException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        $forwardUri = $this->redirectHelper->getForwardUri($request);

        if ($forwardUri && \is_string($forwardUri)) {
            $this->forwardNotFound($event, $forwardUri);
        }
    }

    public function forwardNotFound(ExceptionEvent $event, string $uri): void
    {
        try {
            $this->router->match($uri);

            $request = $event->getRequest();

            $attributes = [];

            $server = \array_merge($request->server->all(), ['REQUEST_URI' => $uri]);
            $subRequest = $request->duplicate(null, null, $attributes, null, null, $server);

            $subResponse = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);

            $canonical = $this->getHeaderLink($request, $uri);

            $subResponse->headers->set('Link', $canonical);

            $event->setResponse($subResponse);
        } catch (\Exception $e) {
            $event->setResponse(new RedirectResponse($uri));
        }
        $event->allowCustomResponseCode();
    }

    private function getHeaderLink(Request $request, string $uri): string
    {
        $url = \vsprintf('%s://%s%s', [
            $request->getScheme(),
            $request->getHttpHost(),
            $uri,
        ]);

        return \sprintf('<%s>; rel="canonical"', $url);
    }
}
