<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;

class RouteConfig
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
     * Dynamic route from elasticms
     *
     * @var bool
     */
    private $emsRoute;

    /**
     * @param string $name
     * @param array  $options
     * @param bool   $emsRoute
     */
    public function __construct($name, array $options, $emsRoute = false)
    {
        $this->name = $name;
        $this->options = $this->resolveOptions($options, $emsRoute);
        $this->emsRoute = $emsRoute;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $prefix = ($this->emsRoute ? 'ems_' : 'emsch_');

        return $prefix.$this->name;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        $path = $this->options['path'];

        if (is_string($path)) {
            return [$this->getName() => $this->createRoute($path)];
        }

        $routes = [];

        if (is_array($path)) {
            foreach ($path as $key => $p) {
                $name = $this->getName() . '.' . $key;
                $routes[$name] = $this->createRoute($p);
            }
        }

        return $routes;
    }

    private function createRoute($path)
    {
        return new Route(
            $path,
            $this->options['defaults'],
            $this->options['requirements'],
            $this->options['options'],
            null,
            null,
            [$this->options['method']]
        );
    }

    /**
     * @param array $options
     * @param bool  $emsRoute
     *
     * @return array
     */
    private function resolveOptions(array $options, bool $emsRoute = false)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired(['path'])
            ->setDefaults([
                'controller' => 'emsch.controller.router::handle',
                'defaults' => [],
                'requirements' => [],
                'options' => [],
                'method' => 'GET',
            ])
            ->setNormalizer('defaults', function(Options $options, $value) {
                return array_merge($value, [
                    '_controller' => $options['controller']
                ]);
            })
        ;

        if ($emsRoute) {
            $resolver
                ->setDefaults(['query' => null])
                ->setRequired(['type', 'template'])
                ->setNormalizer('options', function(Options $options, $value) {
                    $value['type'] = $options['type'];
                    $value['query'] = $options['query'];
                    $value['template'] = $options['template'];

                    return $value;
                })
            ;
        }
        
        return $resolver->resolve($options);
    }
}