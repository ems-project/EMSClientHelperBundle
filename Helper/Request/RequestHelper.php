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
     * @param array        $environments
     */
    public function __construct(RequestStack $requestStack, array $environments)
    {
        $this->requestStack = $requestStack;

        foreach ($environments as $name => $config) {
            $this->environments[] = new Environment($name, $config);
        }
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
            if ($env->matchRequest($request)) {
                $env->modifyRequest($request);
                break;
            }
        }
    }

    /**
     * @return string
     */
    public function getBackend()
    {
        $current = $this->requestStack->getCurrentRequest();

        return ($current ? $current->get('_backend') : null);
    }

    /**
     * Important for twig loader  on kernel terminate we don't have a current request.
     * So this function remembers it's environment and can still return it.
     *
     * @return string
     */
    public function getEnvironment()
    {
        static $env = false;

        if (!$env) {
            $current = $this->requestStack->getCurrentRequest();
            $env = ($current ? $current->get('_environment') : null);
        }

        return $env;
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