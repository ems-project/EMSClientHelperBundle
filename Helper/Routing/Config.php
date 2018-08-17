<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;

class Config
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options;

    /**
     * @param string $name
     * @param array  $options
     */
    public function __construct(string $name, array $options)
    {
        $this->name = $name;
        $this->options = $this->resolveOptions($options);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'ems_'.$this->name;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return new Route(
            $this->options['path'],
            $this->options['defaults'],
            $this->options['requirements'],
            $this->options['options'],
            null,
            null,
            ['GET']
        );
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                'defaults' => ['_controller' => 'emsch.controller.router::handle'],
                'requirements' => [],
                'options' => [],
                'query' => null,
            ])
            ->setRequired(['path', 'type', 'template'])
            ->setNormalizer('options', function(Options $options, $value) {
                $value['type'] = $options['type'];
                $value['query'] = $options['query'];
                $value['template'] = $options['template'];

                return $value;
            })
        ;
        
        return $resolver->resolve($options);
    }
}