<?php

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Request\RequestHelper;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RequestListener
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @return void
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->requestHelper->bindEnvironment($event->getRequest());
    }
    
    /**
     * @param GetResponseForExceptionEvent $event
     * 
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->requestHelper->bindEnvironment($event->getRequest());
    }
}
