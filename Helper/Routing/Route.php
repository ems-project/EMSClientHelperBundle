<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

class Route
{
    /** @var string */
    private $name;
    /** @var array */
    private $options;

    public function __construct(string $name, array $options)
    {
        $this->name = $name;
        $this->options = $this->resolveOptions($options);
    }

    public function addToCollection(RouteCollection $collection, array $locales = []): void
    {
        $path = $this->options['path'];

        if (\is_array($path)) {
            foreach ($path as $key => $p) {
                $locale = \in_array($key, $locales) ? $key : null;
                $route = $this->createRoute($p, $locale);

                $collection->add(\sprintf('%s.%s', $this->name, $key), $route);
            }
        } else {
            $collection->add($this->name, $this->createRoute($path));
        }
    }

    private function createRoute(string $path, ?string $locale = null): SymfonyRoute
    {
        $defaults = $this->options['defaults'];

        if ($locale) {
            $defaults['_locale'] = $locale;
        }

        return new SymfonyRoute(
            $path,
            $defaults,
            $this->options['requirements'],
            $this->options['options'],
            $this->options['host'],
            $this->options['schemes'],
            [$this->options['method']],
            $this->options['condition']
        );
    }

    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired(['path'])
            ->setDefaults([
                'method' => 'GET',
                'controller' => 'emsch.controller.router::handle',
                'defaults' => [],
                'requirements' => [],
                'options' => [],
                'host' => null,
                'schemes' => null,
                'type' => null,
                'query' => null,
                'template' => null,
                'index_regex' => null,
                'condition' => null,
            ])
            ->setNormalizer('defaults', function (Options $options, $value) {
                if (!isset($value['_controller'])) {
                    $value['_controller'] = $options['controller'];
                }

                return $value;
            })
            ->setNormalizer('options', function (Options $options, $value) {
                if (null !== $options['query']) {
                    $query = \json_decode($options['query']);

                    if (JSON_ERROR_NONE !== \json_last_error()) {
                        throw new \LogicException('invalid json for query!');
                    }

                    $value['query'] = \json_encode($query);
                }

                $value['type'] = $options['type'];
                $value['template'] = $options['template'];
                $value['index_regex'] = $options['index_regex'];

                return $value;
            })
        ;

        return $resolver->resolve($options);
    }
}
