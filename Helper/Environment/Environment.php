<?php

namespace EMS\ClientHelperBundle\Helper\Environment;

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
     * @var array
     */
    private $request = [];

    public function __construct($name, array $config)
    {
        $this->name = $name;

        $this->regex = $config['regex'] ?? '*';
        $this->backend = $config['backend'] ?? false;
        $this->index = $config['index'] ?? null;
        $this->request = $config['request'] ?? [];
    }

    /**
     * @return string
     */
    public function getIndex()
    {
        if ($this->index) {
            return $this->index;
        }

        return $this->request['_environment'] ? $this->request['_environment'] : $this->name;
    }

    public function matchRequest(Request $request)
    {
        if (null === $this->regex) {
            return true;
        }

        $url = vsprintf('%s://%s%s', [$request->getScheme(), $request->getHttpHost(), $request->getBasePath()]);

        return 1 === preg_match($this->regex, $url) ? true : false;
    }

    public function modifyRequest(Request $request)
    {
        // backward compatibility
        $request->attributes->set('_environment', $this->getIndex());
        $request->attributes->set('_backend', $this->backend);

        if (!empty($this->request)) {
            foreach ($this->request as $key => $value) {
                $request->attributes->set($key, $value);

                if ('_locale' === $key) {
                    $request->setLocale($value);
                }
            }
        }
    }
}
