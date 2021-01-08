<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\ContentType;

use Elastica\Aggregation\Max;
use Elastica\Aggregation\Terms;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\CommonBundle\Elasticsearch\Response\Response;
use EMS\CommonBundle\Search\Search;
use Psr\Log\LoggerInterface;

final class ContentTypeHelper
{
    private LoggerInterface $logger;
    private ?ContentTypeCollection $contentTypeCollection = null;

    public const AGG_CONTENT_TYPE = 'contentType';
    public const AGG_LAST_PUBLISHED = 'lastPublished';

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function get(ClientRequest $clientRequest, string $contentTypeName): ?ContentType
    {
        return $this->getContentTypeCollection($clientRequest)->getByName($contentTypeName);
    }

    public function getContentTypeCollection(ClientRequest $clientRequest): ContentTypeCollection
    {
        if (null === $this->contentTypeCollection) {
            $this->contentTypeCollection = $this->makeContentTypeCollection($clientRequest);
        }

        return $this->contentTypeCollection;
    }

    private function makeContentTypeCollection(ClientRequest $clientRequest): ContentTypeCollection
    {
        $maxUpdate = new Max(self::AGG_LAST_PUBLISHED);
        $maxUpdate->setField('_published_datetime');

        $lastUpdate = new Terms(self::AGG_CONTENT_TYPE);
        $lastUpdate->setField('_contenttype');
        $lastUpdate->setSize(100);
        $lastUpdate->addAggregation($maxUpdate);

        $alias = $clientRequest->getAlias();
        $search = new Search([$alias]);
        $search->setSize(0);
        $search->addAggregation($lastUpdate);

        $response = Response::fromResultSet($clientRequest->commonSearch($search));

        return ContentTypeCollection::fromResponse($alias, $response);
    }
}
