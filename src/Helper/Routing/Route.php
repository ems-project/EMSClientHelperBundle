<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

final class Route
{
    private string $name;
    /** @var array<mixed> */
    private array $options;

    /**
     * @param array<mixed> $options
     */
    public function __construct(string $name, array $options)
    {
        $this->name = $name;
        $this->options = $this->resolveOptions($options);
    }

    /**
     * @param string[] $locales
     */
    public function addToCollection(RouteCollection $collection, array $locales = []): void
    {
        $path = $this->options['path'];

        if (\is_array($path)) {
            foreach ($path as $key => $p) {
                $locale = \in_array($key, $locales) ? \strval($key) : null;
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

        if (null !== $this->options['prefix']) {
            if ('/' !== \substr($path, 0, 1)) {
                $path = $this->options['prefix'].'/'.$path;
            } else {
                $path = $this->options['prefix'].$path;
            }
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

    /**
     * @param array<mixed> $options
     *
     * @return array<mixed>
     */
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
                'prefix' => null,
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
                    $value['query'] = \json_encode($options['query']);
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
