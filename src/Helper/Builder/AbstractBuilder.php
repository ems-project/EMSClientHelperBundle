<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Builder;

use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Elasticsearch\Response\Response;
use EMS\CommonBundle\Elasticsearch\Response\ResponseInterface;
use EMS\CommonBundle\Search\Search;
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

    private const SEARCH_LIMIT = 1000;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $manager, LoggerInterface $logger, array $locales)
    {
        $this->clientRequest = $manager->getDefault();
        $this->logger = $logger;
        $this->locales = $locales;
    }

    protected function modifySearch(Search $search): void {}

    /**
     * @param array<mixed> $sort
     */
    protected function search(ContentType $contentType): ResponseInterface
    {
        $search = new Search([$contentType->getEnvironment()->getAlias()]);
        $search->setContentTypes([$contentType->getName()]);
        $search->setSize(self::SEARCH_LIMIT);

        $this->modifySearch($search);

        $response = Response::fromResultSet($this->clientRequest->commonSearch($search));

        if ($response->getTotal() > self::SEARCH_LIMIT) {
            $this->logger->error('Only the first {limit} {type}s have been loaded on a total of {total}', [
                'limit' => self::SEARCH_LIMIT,
                'type' => $contentType->getName(),
                'total' => $response->getTotal(),
            ]);
        }

        return $response;
    }
}
