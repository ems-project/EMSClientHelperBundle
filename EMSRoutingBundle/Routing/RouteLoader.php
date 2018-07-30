<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Yaml;

class RouteLoader extends Loader
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $yaml = Yaml::parseFile($this->projectDir . '/' . $resource);

        /** @var Config[] $configs */
        $configs = array_map(function (string $name, array $options) {
            return new Config($name, $options);
        }, array_keys($yaml), $yaml);


        $collection = new RouteCollection();

        foreach ($configs as $config) {
            $collection->add($config->getName(), $config->getRoute());
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'ems' === $type;
    }
}