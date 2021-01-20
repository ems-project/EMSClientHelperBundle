<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

final class RouteConfig
{
    private string $name;
    /** @var array<mixed> */
    private array $config;
    /** @var array<mixed> */
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
     * @param array<mixed> $hit
     */
    public static function fromHit(array $hit): self
    {
        $source = $hit['_source'];
        $name = $source['name'];

        $config = self::decode($source['config']);
        $query = isset($source['query']) ? self::decode($source['query']) : null;

        $routeConfig = new self($name, $config, $query);

        if (isset($source['template_static'])) {
            $routeConfig->setTemplateStatic('@EMSCH/'.$source['template_static']);
        }
        if (isset($source['template_source'])) {
            $routeConfig->setTemplateSource($source['template_source']);
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

    /**
     * @return array<mixed>
     */
    private static function decode(string $json): array
    {
        $result = \json_decode($json, true);

        if (JSON_ERROR_NONE !== \json_last_error()) {
            throw new \InvalidArgumentException(\sprintf('invalid json %s', $json));
        }

        return $result;
    }
}
