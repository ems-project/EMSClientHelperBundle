<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing;

use Psr\Log\LoggerInterface;

final class RouteFactory
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array<mixed> $hit
     */
    public function fromHit(array $hit): ?Route
    {
        $source = $hit['_source'];
        $name = $source['name'];

        try {
            $options = \json_decode($source['config'], true);

            if (JSON_ERROR_NONE !== \json_last_error()) {
                throw new \InvalidArgumentException(\sprintf('invalid json %s', $source['config']));
            }

            $options['query'] = $source['query'] ?? null;

            $staticTemplate = isset($source['template_static']) ? '@EMSCH/'.$source['template_static'] : null;
            $options['template'] = $source['template_source'] ?? $staticTemplate;
            $options['index_regex'] = $source['index_regex'] ?? null;

            return new Route($name, $options);
        } catch (\Throwable $e) {
            $this->logger->error('Router failed to create ems route {name} : {error}', ['name' => $name, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
