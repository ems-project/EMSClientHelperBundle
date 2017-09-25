<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestService
{
    /**
     * @var Request|null
     */
    private $request;
    
    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }
    
    /**
     * @return string
     */
    public function getEnvironment()
    {
        return ($this->request ? $this->request->get('_environment') : '');
    }
    
    /**
     * @return string
     */
    public function getLocale()
    {
        return ($this->request ? $this->request->get('_locale') : '');
    }
}