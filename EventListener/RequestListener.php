<?php

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Routing\RequestEnvironment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RequestListener
{
    /**
     * @var RequestEnvironment[]
     */
    private $requestEnvironments = [];
    
    /**
     * @param string $name
     * @param string $regex
     * @param string $index
     */
    public function addRequestEnvironment($name, $regex, $index, $backend)
    {
        $this->requestEnvironments[] = new RequestEnvironment($name, $regex, $index, $backend);
    }
    
    /**
     * @param GetResponseEvent $event
     *
     * @return void
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->setEnvironment($event->getRequest());
    }
    
    /**
     * @param GetResponseForExceptionEvent $event
     * 
     * @return void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->setEnvironment($event->getRequest());
    }
    
    /**
     * @return RequestEnvironment[]
     */
    public function getRequestEnvironments()
    {
        return $this->requestEnvironments;
    }
    
    /**
     * @param Request $request
     *
     * @return void
     */
    private function setEnvironment(Request $request)
    {
        if (null == $this->requestEnvironments) {
            return;
        }
        
        foreach ($this->requestEnvironments as $requestEnvironment) {
            if ($requestEnvironment->match($request)) {
                $request->attributes->set('_environment', $requestEnvironment->getIndex());
                if(!empty($requestEnvironment->getBackend())) {
                    $request->attributes->set('_backend', $requestEnvironment->getBackend());
                }
                return; //stop on match
            }
        }
    }
}
