<?php

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Routing\RedirectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class RedirectListener implements EventSubscriberInterface
{
    /**
     * @var RedirectHelper
     */
    private $redirectHelper;
    
    /**
     * @var HttpKernelInterface
     */
    private $kernel;
    
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RedirectHelper      $redirectHelper
     * @param HttpKernelInterface $kernel
     * @param RouterInterface     $router
     */
    public function __construct(
        RedirectHelper $redirectHelper,
        HttpKernelInterface $kernel,
        RouterInterface $router
    ) {
        $this->redirectHelper = $redirectHelper;
        $this->kernel = $kernel;
        $this->router = $router;
    }
    
    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }
        
        $exception = $event->getException();
        
        if ($exception instanceof NotFoundHttpException) {
            $this->handleNotFoundException($event);
            return;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [['onKernelException', 15]],
        ];
    }
    
    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @return void
     */
    private function handleNotFoundException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        
        $forwardUri = $this->redirectHelper->getForwardUri($request);

        if ($forwardUri and is_string($forwardUri)) {
            $this->forwardNotFound($event, $forwardUri);
        }
    }

    /**
     * @param GetResponseForExceptionEvent $event
     * @param string                       $uri
     */
    public function forwardNotFound(GetResponseForExceptionEvent $event, string $uri)
    {
        
        try {
            $this->router->match($uri);
            
            $request = $event->getRequest();
         
            $attributes = [];

            $server = array_merge($request->server->all(), ['REQUEST_URI' => $uri]);
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
    
    /**
     * @param Request $request
     * @param string  $uri
     *
     * @return string
     */
    private function getHeaderLink(Request $request, $uri)
    {
        $url = vsprintf('%s://%s%s', [
            $request->getScheme(),
            $request->getHttpHost(),
            $uri,
        ]);
        
        return sprintf('<%s>; rel="canonical"', $url);
    }
}
