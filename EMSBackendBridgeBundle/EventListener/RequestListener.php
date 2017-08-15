<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RequestListener
{
    /**
     * @var array
     */
    private $requestEnvironment;
    
    /**
     * @param array $requestEnvironment
     */
    public function __construct(array $requestEnvironment)
    {
        $this->requestEnvironment = $requestEnvironment;
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
        if (null === $this->requestEnvironment) {
            return;
        }
        
        $environment = $this->getEnvironment($request);
        
        if ($environment) {
            $request->attributes->set('_environment', $environment);
        }
    }
    
    /**
     * @param Request $request
     *
     * @return string|false
     */
    private function getEnvironment(Request $request)
    {
        $url = vsprintf('%s://%s%s', [
            $request->getScheme(),
            $request->getHttpHost(),
            $request->getBasePath(),
        ]);
        
        foreach ($this->requestEnvironment as $env => $regex) {
            if (null == $regex || preg_match($regex, $url)) {
                return $env;
            }
        }
        
        return false;
    }
}
