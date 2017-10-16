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
     * @param string $name
     * @param string $regex
     * @param string $index
     */
    public function __construct($name, $regex = null, $index = null)
    {
        $this->name = $name;
        $this->regex = $regex;
        $this->index = $index;
    }
    
    /**
     * @return string
     */
    public function getIndex()
    {
        return ($this->index ? $this->index : $this->name);
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
