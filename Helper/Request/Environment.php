<?php

namespace EMS\ClientHelperBundle\Helper\Request;

use Symfony\Component\HttpFoundation\Request;

class Environment
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
     * @param array  $config
     */
    public function __construct($name, array $config)
    {
        $this->name = $name;

        $this->index = $config['index'];
        $this->regex = $config['regex'] ?? '*';
        $this->backend = $config['backend'] ?? '*';
    }
    
    /**
     * @return string
     */
    public function getIndex()
    {
        return ($this->index ? $this->index : $this->name);
    }

    /**
     * @return string|null
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
