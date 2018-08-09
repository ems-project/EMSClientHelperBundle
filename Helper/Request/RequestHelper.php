<?php

namespace EMS\ClientHelperBundle\Helper\Request;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestHelper
{
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