<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle;

use Symfony\Component\HttpFoundation\Request;

class RequestEnvironment
{
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var string
     */
    private $regex;
    
    /**
     * @var string
     */
    private $index;

    /**
     * @var string
     */
    private $backend;
    
    /**
     * @param string $name
     * @param string $regex
     * @param string $index
     * @param string $backend
     */
    public function __construct($name, $regex = null, $index = null, $backend = null)
    {
        $this->name = $name;
        $this->regex = $regex;
        $this->index = $index;
        $this->backend = $backend;
    }
    
    /**
     * @return string
     */
    public function getIndex()
    {
        return ($this->index ? $this->index : $this->name);
    }

    /**
     * @return string
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * @param Request $request
     *
     * @return boolean
     */
    public function match(Request $request)
    {
        if (null === $this->regex) {
            return true;
        }
        
        $url = vsprintf('%s://%s%s', [
            $request->getScheme(),
            $request->getHttpHost(),
            $request->getBasePath(),
        ]);
        
        return 1 === preg_match($this->regex, $url) ? true : false;
    }
}
