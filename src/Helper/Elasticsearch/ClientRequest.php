<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

use Elastica\Aggregation\Terms;
use Elastica\Query\AbstractQuery;
use Elastica\ResultSet;
use EMS\ClientHelperBundle\Exception\EnvironmentNotFoundException;
use EMS\ClientHelperBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\Helper\Cache\CacheHelper;
use EMS\ClientHelperBundle\Helper\ContentType\ContentType;
use EMS\ClientHelperBundle\Helper\ContentType\ContentTypeHelper;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\CommonBundle\Common\EMSLink;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
use EMS\CommonBundle\Elasticsearch\Exception\NotFoundException;
use EMS\CommonBundle\Search\Search;
use EMS\CommonBundle\Service\ElasticaService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ClientRequest
{
    private const CONTENT_TYPE_LIMIT = 500;
    private EnvironmentHelper $environmentHelper;
    private CacheHelper $cacheHelper;
    private ContentTypeHelper $contentTypeHelper;
    private LoggerInterface $logger;
    private AdapterInterface $cache;
    /** @var array<string, mixed> */
    private array $options;
    /** @var array<string, \DateTime> */
    private array $lastUpdateByType;
    private string $name;
    private ElasticaService $elasticaService;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        ElasticaService $elasticaService,
        EnvironmentHelper $environmentHelper,
        CacheHelper $cacheHelper,
        ContentTypeHelper $contentTypeHelper,
        LoggerInterface $logger,
        AdapterInterface $cache,
        string $name,
        array $options = []
    ) {
        if (!isset($options['index_prefix'])) {
            throw new \RuntimeException('Client request index_prefix is deprecated and must be removed now: Environment name === Elasticsearch alias name');
        }
        $this->environmentHelper = $environmentHelper;
        $this->cacheHelper = $cacheHelper;
        $this->contentTypeHelper = $contentTypeHelper;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->options = $options;
        $this->elasticaService = $elasticaService;
        $this->lastUpdateByType = [];
        $this->name = $name;
    }

    /**
     * @return string[]
     */
    public function analyze(string $text, string $analyzer): array
    {
        if (empty($text)) {
            return [];
        }

        $this->logger->debug('ClientRequest : analyze {text} with {analyzer}', ['text' => $text, 'analyzer' => $analyzer]);
        $out = [];
        \preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $text, $out);
        $words = $out[0];
        $index = $this->getFirstIndex();

        return $this->elasticaService->filterStopWords($index, $analyzer, $words);
    }

    /**
     * @return array{_id: string, _type?: string, _source: array}
     */
    public function get(string $type, string $id): array
    {
        $this->logger->debug('ClientRequest : get {type}:{id}', ['type' => $type, 'id' => $id]);

        return $this->searchOne($type, [
            'query' => [
                'term' => [
                    '_id' => $id,
                ],
            ],
        ]);
    }

    /**
     * @return string[]
     */
    public function getAllChildren(string $emsKey, string $childrenField): array
    {
        $this->logger->debug('ClientRequest : getAllChildren for {emsKey}', ['emsKey' => $emsKey]);
        $out = [$emsKey];
        $item = $this->getByEmsKey($emsKey);

        if (false === $item) {
            return $out;
        }

        if (isset($item['_source'][$childrenField]) && \is_array($item['_source'][$childrenField])) {
            foreach ($item['_source'][$childrenField] as $key) {
                $out = \array_merge($out, $this->getAllChildren($key, $childrenField));
            }
        }

        return $out;
    }

    /**
     * @param string[] $sourceFields
     *
     * @return array<string, mixed>|false
     */
    public function getByEmsKey(string $emsLink, array $sourceFields = [])
    {
        $type = static::getType($emsLink);
        if (null === $type) {
            throw new \RuntimeException('Unexpected null type');
        }
        $ouuid = static::getOuuid($emsLink);
        if (null === $ouuid) {
            throw new \RuntimeException('Unexpected null ouuid');
        }

        return $this->getByOuuid($type, $ouuid, $sourceFields);
    }

    /**
     * @param string[] $sourceFields
     * @param string[] $source_exclude
     *
     * @return array<string, mixed>|false
     */
    public function getByOuuid(string $type, string $ouuid, array $sourceFields = [], array $source_exclude = [])
    {
        $this->logger->debug('ClientRequest : getByOuuid {type}:{id}', ['type' => $type, 'id' => $ouuid]);
        if (!empty($source_exclude)) {
            @\trigger_error('_source_exclude field are not supported anymore', E_USER_DEPRECATED);
        }

        foreach ($this->elasticaService->getIndicesFromAlias($this->getAlias()) as $index) {
            try {
                $document = $this->elasticaService->getDocument($index, $type, $ouuid, $sourceFields);

                return $document->getRaw();
            } catch (NotFoundException $e) {
            }
        }

        return false;
    }

    /**
     * @param string[] $ouuids
     *
     * @return array<mixed>
     */
    public function getByOuuids(string $type, array $ouuids): array
    {
        $this->logger->debug('ClientRequest : getByOuuids {type}:{id}', ['type' => $type, 'id' => $ouuids]);

        $query = $this->elasticaService->getTermsQuery('_id', $ouuids);
        $query = $this->elasticaService->filterByContentTypes($query, [$type]);
        $search = new Search([$this->getAlias()], $query);

        return $this->elasticaService->search($search)->getResponse()->getData();
    }

    /**
     * @return string[]
     */
    public function getContentTypes(): array
    {
        $search = new Search([$this->getAlias()]);
        $search->setSize(0);
        $terms = new Terms(EMSSource::FIELD_CONTENT_TYPE);
        $terms->setField(EMSSource::FIELD_CONTENT_TYPE);
        $terms->setSize(self::CONTENT_TYPE_LIMIT);
        $search->addAggregation($terms);
        $resultSet = $this->elasticaService->search($search);
        $aggregation = $resultSet->getAggregation(EMSSource::FIELD_CONTENT_TYPE);
        $contentTypes = [];
        foreach ($aggregation['buckets'] ?? [] as $bucket) {
            $contentTypes[] = $bucket['key'];
        }
        if (\count($contentTypes) >= self::CONTENT_TYPE_LIMIT) {
            $this->logger->warning('The get content type function is only considering the first {limit} content type', ['limit' => self::CONTENT_TYPE_LIMIT]);
        }

        return $contentTypes;
    }

    public function getFieldAnalyzer(string $field): string
    {
        $this->logger->debug('ClientRequest : getFieldAnalyzer {field}', ['field' => $field]);

        return $this->elasticaService->getFieldAnalyzer($this->getFirstIndex(), $field);
    }

    /**
     * @param string[] $sourceFields
     */
    public function getHierarchy(string $emsKey, string $childrenField, int $depth = null, array $sourceFields = [], EMSLink $activeChild = null): ?HierarchicalStructure
    {
        $this->logger->debug('ClientRequest : getHierarchy for {emsKey}', ['emsKey' => $emsKey]);
        $item = $this->getByEmsKey($emsKey, $sourceFields);

        if (false === $item) {
            return null;
        }
        $contentType = $item['_source'][EMSSource::FIELD_CONTENT_TYPE] ?? $item['_type'];
        $out = new HierarchicalStructure($contentType, $item['_id'], $item['_source'], $activeChild);

        if (null === $depth || $depth) {
            if (isset($item['_source'][$childrenField]) && \is_array($item['_source'][$childrenField])) {
                foreach ($item['_source'][$childrenField] as $key) {
                    if ($key) {
                        $child = $this->getHierarchy($key, $childrenField, (null === $depth ? null : $depth - 1), $sourceFields, $activeChild);
                        if ($child) {
                            $out->addChild($child);
                        }
                    }
                }
            }
        }

        return $out;
    }

    public function mustBeBind(): bool
    {
        return $this->options['must_be_bind'] ?? true;
    }

    public function hasEnvironments(): bool
    {
        return \count($this->getEnvironments()) > 0;
    }

    public function isBind(): bool
    {
        return $this->hasEnvironments() && null !== $this->environmentHelper->getBindEnvironmentName();
    }

    /**
     * @return Environment[]
     */
    public function getEnvironments(): array
    {
        return $this->environmentHelper->getEnvironments();
    }

    public function getCurrentEnvironment(): Environment
    {
        return $this->environmentHelper->getCurrentEnvironment();
    }

    public function cacheContentType(ContentType $contentType): void
    {
        $this->cacheHelper->saveContentType($contentType);
    }

    public function getRouteContentType(): ?ContentType
    {
        return $this->getContentType($this->getOption('[route_type]'));
    }

    public function getTranslationContentType(): ?ContentType
    {
        return $this->getContentType($this->getOption('[translation_type]'));
    }

    public function getContentType(string $name): ?ContentType
    {
        if (null === $contentType = $this->contentTypeHelper->get($this, $name)) {
            return null;
        }

        $cachedContentType = $this->cacheHelper->getContentType($contentType);

        return $cachedContentType ? $cachedContentType : $contentType;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->environmentHelper->getLocale();
    }

    /**
     * @return string|null
     */
    public static function getOuuid(string $emsLink)
    {
        if (!\strpos($emsLink, ':')) {
            return $emsLink;
        }

        $split = \preg_split('/:/', $emsLink);
        if (!\is_array($split)) {
            throw new \RuntimeException(\sprintf('Unexpected not support emslink format : %s', $emsLink));
        }
        $ouuid = \end($split);

        if (false === $ouuid) {
            return null;
        }

        return $ouuid;
    }

    public function hasOption(string $option): bool
    {
        return isset($this->options[$option]) && null != $this->options[$option];
    }

    /**
     * @param mixed $default
     *
     * @return mixed|null
     */
    public function getOption(string $propertyPath, $default = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (!$propertyAccessor->isReadable($this->options, $propertyPath)) {
            return $default;
        }

        return $propertyAccessor->getValue($this->options, $propertyPath);
    }

    public static function getType(string $emsLink): ?string
    {
        if (!\strpos($emsLink, ':')) {
            return $emsLink;
        }

        $split = \preg_split('/:/', $emsLink);

        if (\is_array($split) && \is_string($split[0] ?? null)) {
            return $split[0];
        }

        return null;
    }

    /**
     * @param string|string[]|null $type
     * @param array<mixed>         $body
     * @param string[]             $sourceExclude
     *
     * @return array<mixed>
     */
    public function search($type, array $body, int $from = 0, int $size = 10, array $sourceExclude = [], ?string $regex = null, string $index = null)
    {
        if (null === $type) {
            $types = [];
        } elseif (\is_array($type)) {
            $types = $type;
        } else {
            $types = \explode(',', $type);
        }

        if (null === $index) {
            $index = $this->getAlias();
        }

        $arguments = [
            'index' => $index,
            'type' => $type,
            'body' => $body,
            'size' => $body['size'] ?? $size,
            'from' => $body['from'] ?? $from,
        ];

        if (!empty($sourceExclude)) {
            @\trigger_error('_source_exclude field are not supported anymore', E_USER_DEPRECATED);
        }

        $this->logger->debug('ClientRequest : search for {type}', $arguments);
        $search = $this->elasticaService->convertElasticsearchSearch($arguments);
        $search->setContentTypes($types);
        $search->setRegex($regex);
        $resultSet = $this->elasticaService->search($search);

        return $resultSet->getResponse()->getData();
    }

    /**
     * @param string[] $types
     */
    public function initializeCommonSearch(array $types, ?AbstractQuery $query = null): Search
    {
        $query = $this->elasticaService->filterByContentTypes($query, $types);

        return new Search([$this->getAlias()], $query);
    }

    public function commonSearch(Search $search): ResultSet
    {
        return $this->elasticaService->search($search);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<mixed>
     */
    public function searchArgs(array $arguments): array
    {
        if (!isset($arguments['index'])) {
            $arguments['index'] = $this->getAlias();
        }
        $search = $this->elasticaService->convertElasticsearchSearch($arguments);

        return $this->elasticaService->search($search)->getResponse()->getData();
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<mixed>
     */
    public function searchBy(string $type, array $parameters, int $from = 0, int $size = 10): array
    {
        $this->logger->debug('ClientRequest : searchBy for type {type}', ['type' => $type]);
        $body = [
            'query' => [
                'bool' => [
                    'must' => [],
                ],
            ],
        ];

        foreach ($parameters as $id => $value) {
            $body['query']['bool']['must'][] = [
                'term' => [
                    $id => [
                        'value' => $value,
                    ],
                ],
            ];
        }

        $search = $this->elasticaService->convertElasticsearchSearch([
            'index' => $this->getAlias(),
            'type' => $type,
            'body' => $body,
            'size' => $size,
            'from' => $from,
        ]);

        return $this->elasticaService->search($search)->getResponse()->getData();
    }

    /**
     * @param string|string[]      $type
     * @param array<string, mixed> $body
     *
     * @return array{_id: string, _type?: string, _source: array}
     */
    public function searchOne($type, array $body, ?string $indexRegex = null): array
    {
        $this->logger->debug('ClientRequest : searchOne for {type}', ['type' => $type, 'body' => $body, 'indexRegex' => $indexRegex]);
        $search = $this->search($type, $body, 0, 2, [], $indexRegex);

        $hits = $search['hits'];

        if (1 !== \count($hits['hits'])) {
            throw new SingleResultException(\sprintf('expected 1 result, got %d', $hits['hits']));
        }

        return $hits['hits'][0];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{_id:string,_type:string,_source:array<mixed>}|null
     */
    public function searchOneBy(string $type, array $parameters): ?array
    {
        $this->logger->debug('ClientRequest : searchOneBy for type {type}', ['type' => $type]);

        $result = $this->searchBy($type, $parameters, 0, 1);

        if (1 == $result['hits']['total']) {
            return $result['hits']['hits'][0];
        }

        return null;
    }

    /**
     * @param string[] $filter
     *
     * @return array<mixed>
     */
    public function scroll(string $type, array $filter = [], int $size = 10, string $scrollId = null): array
    {
        $scrollTimeout = '30s';

        if ($scrollId) {
            return $this->elasticaService->nextScroll($scrollId, $scrollTimeout)->getData();
        }

        $search = $this->elasticaService->convertElasticsearchSearch([
            'index' => $this->getAlias(),
            'type' => $type,
            '_source' => $filter,
            'size' => $size,
        ]);

        return $this->elasticaService->scrollById($search, $scrollTimeout)->getResponse()->getData();
    }

    /**
     * @param array<mixed> $params
     *
     * @return \Generator<array>
     */
    public function scrollAll(array $params, string $timeout = '30s', string $index = null): iterable
    {
        if (null === $index) {
            $index = $this->getAlias();
        }
        $params['index'] = $index;
        $search = $this->elasticaService->convertElasticsearchSearch($params);
        $scroll = $this->elasticaService->scroll($search, $timeout);

        foreach ($scroll as $resultSet) {
            foreach ($resultSet as $result) {
                if (false === $result) {
                    continue;
                }
                yield $result->getHit();
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCacheKey(string $prefix = '', string $environment = null): string
    {
        if (null === $environment) {
            $environment = $this->environmentHelper->getBindEnvironmentName();
        }

        return $prefix.$environment;
    }

    public function getAlias(): string
    {
        $name = $this->environmentHelper->getBindEnvironmentName();
        if (null === $name) {
            throw new EnvironmentNotFoundException();
        }

        return $name;
    }

    /**
     * @return string
     */
    private function getFirstIndex()
    {
        return $this->elasticaService->getIndexFromAlias($this->getAlias());
    }

    /**
     * @param array<mixed> $cacheKey
     */
    public function getCacheResponse(array $cacheKey, ?string $type, callable $function): Response
    {
        if (null === $type) {
            return $function();
        }
        $jsonEncoded = \json_encode($cacheKey);
        if (false === $jsonEncoded) {
            throw new \RuntimeException('Unexpected false json_encode result');
        }
        $cacheHash = \sha1($jsonEncoded);

        $cachedHierarchy = $this->cache->getItem($cacheHash);

        $contentType = $this->getContentType($type);
        $lastUpdate = $contentType ? $contentType->getLastPublished() : new \DateTimeImmutable('Wed, 09 Feb 1977 16:00:00 GMT');

        /** @var Response $response */
        $response = $cachedHierarchy->get();
        if (!$cachedHierarchy->isHit() || $response->getLastModified() != $lastUpdate) {
            $response = $function();
            $response->setLastModified($lastUpdate);
            $this->cache->save($cachedHierarchy->set($response));
            $this->logger->notice('log.cache_missed', [
                'cache_key' => $cacheHash,
                'type' => $type,
            ]);
        } else {
            $this->logger->notice('log.cache_hit', [
                'cache_key' => $cacheHash,
                'type' => $type,
            ]);
            $response = $cachedHierarchy->get();
        }

        return $response;
    }
}
