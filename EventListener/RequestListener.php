<?php

namespace EMS\ClientHelperBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

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
        if (null === $this->requestEnvironment || !$event->isMasterRequest()) {
            return;
        }
        
        $environment = $this->getEnvironment($event->getRequest());
        
        if ($environment) {
            $event->getRequest()->attributes->set('_environment', $environment);
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
