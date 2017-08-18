<?php

namespace EMS\ClientHelperBundle\EMSRedirectBundle\EventSubscriber;

use EMS\ClientHelperBundle\EMSRedirectBundle\Service\RedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelSubscriber implements EventSubscriberInterface
{
    /**
     * @var RedirectService
     */
    private $redirectService;
    
    /**
     * @var HttpKernel
     */
    private $kernel;

    /**
     * @param RedirectService $redirectService
     * @param HttpKernel      $kernel
     */
    public function __construct(
        RedirectService $redirectService,
        HttpKernel $kernel
    ) {
        $this->redirectService = $redirectService;
        $this->kernel = $kernel;
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
        
        $forwardUri = $this->redirectService->getForwardUri($request);

        if ($forwardUri) {
            $this->forwardNotFound($event, $forwardUri);
        }
    }
    
    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function forwardNotFound(GetResponseForExceptionEvent $event, $uri)
    {
        $request = $event->getRequest();
     
        $attributes = array();
        
        $server = array_merge($request->server->all(), ['REQUEST_URI' => $uri]);
        $subRequest = $request->duplicate(null, null, $attributes, null,  null, $server);
        
        $subResponse = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        $subResponse->headers->set('Link', $this->getHeaderLink($request, $uri));

        $event->setResponse($subResponse);
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
