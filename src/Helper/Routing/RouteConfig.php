<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use EMS\CommonBundle\Common\Json;

final class RouteConfig
{
    private string $name;
    /** @var array<mixed> */
    private array $config;
    /** @var ?array<mixed> */
    private ?array $query;
    private ?string $templateStatic = null;
    private ?string $templateSource = null;

    /**
     * @param array<mixed>  $config
     * @param ?array<mixed> $query
     */
    private function __construct(string $name, array $config, ?array $query)
    {
        $this->name = $name;
        $this->config = $config;
        $this->query = $query;
    }

    /**
     * @param array{config: string|array, query: string|null|array, template_static: string, template_source: string} $options
     */
    public static function fromArray(string $name, array $options): self
    {
        $config = \is_string($options['config']) ? Json::decode($options['config']) : $options['config'];

        if (isset($options['query'])) {
            $query = \is_string($options['query']) ? Json::decode($options['query']) : $options['query'];
        }

        $routeConfig = new self($name, $config, ($query ?? null));

        if (isset($options['template_static'])) {
            $routeConfig->setTemplateStatic('@EMSCH/'.$options['template_static']);
        }
        if (isset($options['template_source'])) {
            $routeConfig->setTemplateSource($options['template_source']);
        }

        return $routeConfig;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return \array_filter([
            'template_static' => $this->templateStatic,
            'template_source' => $this->templateSource,
            'config' => $this->config,
            'query' => $this->query,
        ]);
    }

    /**
     * @return array<mixed>
     */
    public function getOptions(): array
    {
        return \array_merge($this->config, [
            'query' => $this->query,
            'template' => $this->getTemplate(),
        ]);
    }

    private function getTemplate(): string
    {
        if (null !== $this->templateStatic) {
            return $this->templateStatic;
        }

        if (null !== $this->templateSource) {
            return $this->templateSource;
        }

        return '[template]';
    }

    public function setTemplateStatic(string $templateStatic): void
    {
        $this->templateStatic = $templateStatic;
    }

    public function setTemplateSource(string $templateSource): void
    {
        $this->templateSource = $templateSource;
    }
}
