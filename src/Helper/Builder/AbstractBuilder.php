<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Builder;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for client builders.
 *
 * @see \EMS\ClientHelperBundle\Helper\Routing\RoutingBuilder
 * @see \EMS\ClientHelperBundle\Helper\Templating\TemplateBuilder
 * @see \EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder
 */
abstract class AbstractBuilder
{
    protected ClientRequest $clientRequest;
    protected LoggerInterface $logger;
    /** @var string[] */
    protected array $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, LoggerInterface $logger, array $locales)
    {
        $this->clientRequest = $manager->getDefault();
        $this->logger = $logger;
        $this->locales = $locales;
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    protected function search(ContentType $contentType, array $body = []): array
    {
        $limit = 1000;
        $type = $contentType->getName();
        $alias = $contentType->getEnvironment()->getAlias();

        $search = $this->clientRequest->search($type, $body, 0, $limit, [], $alias);
        $total = $search['hits']['total']['value'] ?? $search['hits']['total'];

        if ($total > $limit) {
            $this->logger->error('Only the first {limit} {type}s have been loaded on a total of {total}', [
                'limit' => $limit,
                'type' => $type,
                'total' => $total,
            ]);
        }

        return $search['hits']['hits'];
    }
}
