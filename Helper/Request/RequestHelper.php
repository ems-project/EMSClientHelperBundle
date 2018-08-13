<?php

namespace EMS\ClientHelperBundle\Helper\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
{
    /**
     * @var Environment[]
     */
    private $environments = [];

    /**
     * @var RequestStack
     */
    private $requestStack;
    
    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $name
     * @param string $regex
     * @param string $index
     * @param string $backend
     */
    public function addEnvironment($name, $regex, $index, $backend)
    {
        $this->environments[] = new Environment($name, $regex, $index, $backend);
    }

    /**
     * @return Environment[]
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    public function bindEnvironment(Request $request)
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

    /**
     * @return string
     */
    public function getEnvironment()
    {
        $current = $this->requestStack->getCurrentRequest();
        
        return ($current ? $current->get('_environment') : null);
    }
    
    /**
     * @return string
     */
    public function getLocale()
    {
        $current = $this->requestStack->getCurrentRequest();
        
        return ($current ? $current->getLocale() : null);
    }
}