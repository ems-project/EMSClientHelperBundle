<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\ContentType;

use EMS\CommonBundle\Elasticsearch\Response\Response;

final class ContentTypeCollection
{
    /** @var array<ContentType> */
    private array $contentTypes = [];

    public static function fromResponse(string $alias, Response $response): ContentTypeCollection
    {
        $collection = new self();

        if (null === $aggContentType = $response->getAggregation(ContentTypeHelper::AGG_CONTENT_TYPE)) {
            return $collection;
        }

        foreach ($aggContentType->getBuckets() as $contentTypeBucket) {
            if (null === $contentTypeName = $contentTypeBucket->getKey()) {
                continue;
            }

            $contentType = new ContentType($alias, $contentTypeName, $contentTypeBucket->getCount());

            $contentTypeBucketRaw = $contentTypeBucket->getRaw();
            $lastPublishedValue = $contentTypeBucketRaw[ContentTypeHelper::AGG_LAST_PUBLISHED]['value_as_string'] ?? [];
            if (null !== $lastPublishedValue) {
                $contentType->setLastPublishedValue($lastPublishedValue);
            }

            $collection->contentTypes[$contentType->getName()] = $contentType;
        }

        return $collection;
    }

    public function getByName(string $name): ?ContentType
    {
        return $this->contentTypes[$name] ?? null;
    }
}
