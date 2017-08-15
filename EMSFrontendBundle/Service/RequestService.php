<?php

namespace EMS\ClientHelperBundle\EMSFrontendBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestService
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
        return $this->requestStack->getCurrentRequest()->get('_environment');
    }
    
    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->requestStack->getCurrentRequest()->get('_locale');
    }
}