<?php

namespace EMS\ClientHelperBundle\EventListener;

use EMS\ClientHelperBundle\Helper\Request\Environment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class RequestListener
{
    /**
     * @var Environment[]
     */
    private $environments = [];

    /**
     * @param string $name
     * @param string $regex
     * @param string $index
     * @param string $backend
     */
    public function addRequestEnvironment($name, $regex, $index, $backend)
    {
        $this->environments[] = new Environment($name, $regex, $index, $backend);
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
     * @return Environment[]
     */
    public function getEnvironments()
    {
        return $this->environments;
    }
    
    /**
     * @param Request $request
     *
     * @return void
     */
    private function setEnvironment(Request $request)
    {
        if (null == $this->environments) {
            return;
        }
        
        foreach ($this->environments as $env) {
            if ($env->match($request)) {
                $request->attributes->set('_environment', $env->getIndex());
                if(!empty($env->getBackend())) {
                    $request->attributes->set('_backend', $env->getBackend());
                }
                return; //stop on match
            }
        }
    }
}
