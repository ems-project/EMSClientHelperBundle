<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\EventListener;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\RequestEnvironment;
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
     * @param string $backendLink
     */
	public function addRequestEnvironment($name, $regex, $index, $backendUrl)
    {
    	$this->requestEnvironments[] = new RequestEnvironment($name, $regex, $index, $backendUrl);
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
        		$request->attributes->set('_backend_url', $requestEnvironment->getBackendUrl());
                
                return; //stop on match
            }
        }
    }
}
