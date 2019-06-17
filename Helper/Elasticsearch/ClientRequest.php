<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use EMS\ClientHelperBundle\Exception\EnvironmentNotFoundException;
use EMS\ClientHelperBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\CommonBundle\Common\EMSLink;
use EMS\CommonBundle\Helper\EmsFields;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ClientRequest
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var EnvironmentHelper
     */
    private $environmentHelper;

    /**
     * @var string
     */
    private $indexPrefix;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $lastUpdateByType = [];

    /**
     * @var string
     */
    private $name;

    const OPTION_INDEX_PREFIX = 'index_prefix';

    /**
     * @param Client            $client
     * @param EnvironmentHelper $environmentHelper
     * @param LoggerInterface   $logger
     * @param AdapterInterface   $cache
     * @param string            $name
     * @param array             $options
     */
    public function __construct(
        Client $client,
        EnvironmentHelper $environmentHelper,
        LoggerInterface $logger,
        AdapterInterface $cache,
        $name,
        array $options = []
    ) {
        $this->client = $client;
        $this->environmentHelper = $environmentHelper;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->options = $options;
        $this->lastUpdateByType = [];
        $this->indexPrefix = isset($options[self::OPTION_INDEX_PREFIX]) ? $options[self::OPTION_INDEX_PREFIX] : null;
        $this->name = $name;
    }

    /**
     * @param string $text
     * @param string $analyzer
     *
     * @return array
     */
    public function analyze(string $text, $analyzer)
    {
        if (empty($text)) {
            return [];
        }

        $this->logger->debug('ClientRequest : analyze {text} with {analyzer}', ['text' => $text, 'analyzer' => $analyzer]);
        $out = [];
        preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $text, $out);
        $words = $out[0];


        $params = [
            'index' => $this->getFirstIndex(),
            'body' => [
                'analyzer' => $analyzer,
            ]
        ];

        $withoutStopWords = [];
        foreach ($words as $word) {
            $params['body']['text'] = $word;
            $analyzed = $this->client->indices()->analyze($params);
            if (isset($analyzed['tokens'][0]['token'])) {
                $withoutStopWords[] = $word;
            }
        }
        return $withoutStopWords;
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return array
     *
     * @throws SingleResultException
     */
    public function get($type, $id)
    {
        $this->logger->debug('ClientRequest : get {type}:{id}', ['type' => $type, 'id' => $id]);

        return $this->searchOne($type, [
            'query' => [
                'term' => [
                    '_id' => $id
                ]
            ],
        ]);
    }

    public function getAllChildren(string $emsKey, string $childrenField): array
    {
        $this->logger->debug('ClientRequest : getAllChildren for {emsKey}', ['emsKey' => $emsKey]);
        $out = [$emsKey];
        $item = $this->getByEmsKey($emsKey);

        if (isset($item['_source'][$childrenField]) && is_array($item['_source'][$childrenField])) {
            foreach ($item['_source'][$childrenField] as $key) {
                $out = array_merge($out, $this->getAllChildren($key, $childrenField));
            }
        }

        return $out;
    }

    /**
     * @param string $emsLink
     * @param array  $sourceFields
     *
     * @return array|bool
     */
    public function getByEmsKey($emsLink, array $sourceFields = [])
    {
        return $this->getByOuuid(static::getType($emsLink), static::getOuuid($emsLink), $sourceFields);
    }

    /**
     * @param string $type
     * @param string $ouuid
     * @param array  $sourceFields
     * @param array  $source_exclude
     *
     * @return array | boolean
     */
    public function getByOuuid($type, $ouuid, array $sourceFields = [], array $source_exclude = [])
    {
        $this->logger->debug('ClientRequest : getByOuuid {type}:{id}', ['type' => $type, 'id' => $ouuid]);
        $arguments = [
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => [
                'query' => [
                    'term' => [
                        '_id' => $ouuid
                    ]
                ]
            ]
        ];

        if (!empty($sourceFields)) {
            $arguments['_source'] = $sourceFields;
        }
        if (!empty($source_exclude)) {
            $arguments['_source_exclude'] = $source_exclude;
        }

        $result = $this->client->search($arguments);

        if (isset($result['hits']['hits'][0])) {
            return $result['hits']['hits'][0];
        }

        return false;
    }

    /**
     * @param string $type
     * @param string $ouuids
     *
     * @return array
     */
    public function getByOuuids($type, $ouuids)
    {
        $this->logger->debug('ClientRequest : getByOuuids {type}:{id}', ['type' => $type, 'id' => $ouuids]);

        return $this->client->search([
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => [
                'query' => [
                    'terms' => [
                        '_id' => $ouuids
                    ]
                ]
            ]
        ]);
    }

    /**
     * @return array
     */
    public function getContentTypes()
    {
        $index = $this->getIndex();
        $info = $this->client->indices()->getMapping(['index' => $index]);
        $mapping = array_shift($info);

        return array_keys($mapping['mappings']);
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public function getFieldAnalyzer($field)
    {
        $this->logger->debug('ClientRequest : getFieldAnalyzer {field}', ['field' => $field]);
        $info = $this->client->indices()->getFieldMapping([
            'index' => $this->getFirstIndex(),
            'field' => $field,
        ]);

        $analyzer = 'standard';
        while (is_array($info = array_shift($info))) {
            if (isset($info['analyzer'])) {
                $analyzer = $info['analyzer'];
            } else if (isset($info['mapping'])) {
                $info = $info['mapping'];
            }
        }
        return $analyzer;
    }

    public function getHierarchy(string $emsKey, string $childrenField, int $depth = null, array $sourceFields = [], EMSLink $activeChild = null): ?HierarchicalStructure
    {
        $this->logger->debug('ClientRequest : getHierarchy for {emsKey}', ['emsKey' => $emsKey]);
        $item = $this->getByEmsKey($emsKey, $sourceFields);

        if (empty($item)) {
            return null;
        }

        $out = new HierarchicalStructure($item['_type'], $item['_id'], $item['_source'], $activeChild);

        if ($depth === null || $depth) {
            if (isset($item['_source'][$childrenField]) && is_array($item['_source'][$childrenField])) {
                foreach ($item['_source'][$childrenField] as $key) {
                    if ($key) {
                        $child = $this->getHierarchy($key, $childrenField, ($depth === null ? null : $depth - 1), $sourceFields, $activeChild);
                        if ($child) {
                            $out->addChild($child);
                        }
                    }
                }
            }
        }

        return $out;
    }

    public function getEnvironments() : array
    {
        $environments = [];
        /** @var Environment $environment */
        foreach ($this->environmentHelper->getEnvironments() as $environment) {
            $environments[] = $environment->getIndex();
        }
        return $environments;
    }

    public function getLastChangeDate(string $type): \DateTime
    {
        if (empty($this->lastUpdateByType)) {
            $body = [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'terms' => [
                                    EmsFields::LOG_OPERATION_FIELD => [
                                        EmsFields::LOG_OPERATION_UPDATE,
                                        EmsFields::LOG_OPERATION_DELETE,
                                        EmsFields::LOG_OPERATION_CREATE,
                                    ]
                                ]
                            ],
                            [
                                'terms' => [
                                    EmsFields::LOG_ENVIRONMENT_FIELD => $this->getEnvironments()
                                ]
                            ],
                            [
                                'terms' => [
                                    EmsFields::LOG_INSTANCE_ID_FIELD => $this->getPrefixes()
                                ]
                            ],
                        ]
                    ]
                ],
                'aggs' => [
                    'lastUpdate' => [
                        'terms' => [
                            'field' => EmsFields::LOG_CONTENTTYPE_FIELD,
                            'size' => 100,
                        ],
                        'aggs' => [
                            'maxUpdate' => [
                                'max' => [
                                    'field' => EmsFields::LOG_DATETIME_FIELD
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            try {
                $result =  $this->client->search([
                    'index' => EmsFields::LOG_ALIAS,
                    'type' => EmsFields::LOG_TYPE,
                    'body' => $body,
                ]);

                foreach ($result['aggregations']['lastUpdate']['buckets'] as $maxDate) {
                    $this->lastUpdateByType[$maxDate['key']] = new \DateTime($maxDate['maxUpdate']['value_as_string']);
                }
            } catch (Missing404Exception $e) {
                $this->logger->warning('log.ems_log_alias_not_found', [
                    'alias' => EmsFields::LOG_ALIAS,
                ]);
            }
        }


        if (! empty($this->lastUpdateByType)) {
            $mostRecentUpdate = new \DateTime('2019-06-01T12:00:00Z');
            $types = explode(',', $type);
            foreach ($types as $currentType) {
                if (isset($this->lastUpdateByType[$currentType]) && $mostRecentUpdate < $this->lastUpdateByType[$currentType]) {
                    $mostRecentUpdate = $this->lastUpdateByType[$currentType];
                }
            }
            $this->logger->info('log.last_update_date', [
                'contenttypes' => $type,
                'lastupdate' => $mostRecentUpdate->format('c')
            ]);
            return $mostRecentUpdate;
        }

        $this->logger->warning('log.ems_log_not_found', [
            'alias' => EmsFields::LOG_ALIAS,
            'type' => EmsFields::LOG_TYPE,
            'types' => $type,
            'environments' => $this->getEnvironments(),
            'instance_ids' => $this->getPrefixes(),
        ]);

        $result = $this->search($type, [
            'sort' => ['_published_datetime' => ['order' => 'desc', 'missing' => '_last']],
            '_source' => '_published_datetime'
        ], 0, 1);

        if ($result['hits']['total'] > 0 && isset($result['hits']['hits']['0']['_source']['_published_datetime'])) {
            return new \DateTime($result['hits']['hits']['0']['_source']['_published_datetime']);
        }

        return new \DateTime('Wed, 09 Feb 1977 16:00:00 GMT');
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->environmentHelper->getLocale();
    }

    /**
     * @param string $emsLink
     *
     * @return string|null
     */
    public static function getOuuid($emsLink)
    {
        if (!strpos($emsLink, ':')) {
            return $emsLink;
        }

        $split = preg_split('/:/', $emsLink);

        return array_pop($split);
    }

    /**
     * @param string $option
     *
     * @return bool
     */
    public function hasOption(string $option): bool
    {
        return isset($this->options[$option]) && null != $this->options[$option];
    }

    /**
     * @param string $propertyPath
     * @param string $default
     *
     * @return mixed
     */
    public function getOption($propertyPath, $default = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if (!$propertyAccessor->isReadable($this->options, $propertyPath)) {
            return $default;
        }

        return $propertyAccessor->getValue($this->options, $propertyPath);
    }

    /**
     * @return array
     */
    public function getPrefixes()
    {
        return explode('|', $this->indexPrefix);
    }

    /**
     * @param string $emsLink
     *
     * @return string|null
     */
    public static function getType($emsLink)
    {
        if (!strpos($emsLink, ':')) {
            return $emsLink;
        }

        $split = preg_split('/:/', $emsLink);

        return $split[0];
    }

    /**
     * @param string|array $type
     * @param array        $body
     * @param int          $from
     * @param int          $size
     * @param array        $sourceExclude
     *
     * @return array
     */
    public function search($type, array $body, $from = 0, $size = 10, array $sourceExclude = [])
    {
        $arguments = [
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
            'size' => $body['size'] ?? $size,
            'from' => $body['from'] ?? $from,
        ];

        if (!empty($sourceExclude)) {
            $arguments['_source_exclude'] = $sourceExclude;
        }

        $this->logger->debug('ClientRequest : search for {type}', $arguments);
        return $this->client->search($arguments);
    }

    /**
     * @param array $arguments
     *
     * @return array
     *
     * @throws EnvironmentNotFoundException
     */
    public function searchArgs(array $arguments)
    {
        if (!isset($arguments['index'])) {
            $arguments['index'] = $this->getIndex();
        }

        return $this->client->search($arguments);
    }

    /**
     * http://stackoverflow.com/questions/10836142/elasticsearch-duplicate-results-with-paging
     *
     * @param string|array $type
     * @param array        $body
     * @param int          $pageSize
     *
     * @return array
     */
    public function searchAll($type, array $body, $pageSize = 10)
    {
        $this->logger->debug('ClientRequest : searchAll for {type}', ['type' => $type, 'body' => $body]);
        $arguments = [
            'preference' => '_primary', //see function description
            //TODO: should be replace by an order by _ouid (in case of insert in the index the pagination will be inconsistent)
            'from' => 0,
            'size' => 0,
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
        ];

        $totalSearch = $this->client->search($arguments);
        $total = $totalSearch["hits"]["total"];

        $results = [];
        $arguments['size'] = $pageSize;

        while ($arguments['from'] < $total) {
            $search = $this->client->search($arguments);

            foreach ($search["hits"]["hits"] as $document) {
                $results[] = $document;
            }

            $arguments['from'] += $pageSize;
        }

        return $results;
    }

    /**
     * @param string $type
     * @param array  $parameters
     * @param int    $from
     * @param int    $size
     *
     * @return array
     */
    public function searchBy($type, $parameters, $from = 0, $size = 10)
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
                    ]
                ]
            ];
        }

        return $this->client->search([
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
            'size' => $size,
            'from' => $from,
        ]);
    }

    /**
     * @param string $type
     * @param array  $body
     *
     * @return array
     *
     * @throws SingleResultException
     */
    public function searchOne($type, array $body)
    {
        $this->logger->debug('ClientRequest : searchOne for {type}', ['type' => $type, 'body' => $body]);
        $search = $this->search($type, $body);

        $hits = $search['hits'];

        if (1 != $hits['total']) {
            throw new SingleResultException(sprintf('expected 1 result, got %d', $hits['total']));
        }

        return $hits['hits'][0];
    }

    public function searchOneBy(string $type, array $parameters): ?array
    {
        $this->logger->debug('ClientRequest : searchOneBy for type {type}', ['type' => $type]);

        $result = $this->searchBy($type, $parameters, 0, 1);

        if ($result['hits']['total'] == 1) {
            return $result['hits']['hits'][0];
        }

        return null;
    }

    /**
     * @param string $type
     * @param array  $filter
     * @param int    $size
     * @param string $scrollId
     *
     * @return array
     */
    public function scroll($type, $filter = [], $size = 10, $scrollId = null)
    {
        $scrollTimeout = '30s';

        if ($scrollId) {
            return $this->client->scroll([
                'scroll_id' => $scrollId,
                'scroll' => $scrollTimeout,
            ]);
        }

        $params = [
            'index'  => $this->getIndex(),
            'type'   => $type,
            '_source' => $filter,
            'size'   => $size,
            'scroll' => $scrollTimeout
        ];

        if ($scrollId) {
            $params['scroll_id'] = $scrollId;
        }

        return $this->client->search($params);
    }

    /**
     * @param array  $params
     * @param string $timeout
     *
     * @return \Generator
     *
     * @throws EnvironmentNotFoundException
     */
    public function scrollAll(array $params, $timeout = '30s'): iterable
    {
        $params['scroll'] = $timeout;

        if (!isset($params['index'])) {
            $params['index'] = $this->getIndex();
        }

        $response = $this->client->search($params);

        while (isset($response['hits']['hits']) && count($response['hits']['hits']) > 0) {
            $scrollId = $response['_scroll_id'];

            foreach ($response['hits']['hits'] as $hit) {
                yield $hit;
            }

            $response = $this->client->scroll([
                'scroll_id' => $scrollId,
                'scroll' => $timeout
            ]);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $prefix
     *
     * @return string
     *
     * @throws EnvironmentNotFoundException
     *
     * @todo rename to getEnvironmentAlias?
     */
    public function getCacheKey(string $prefix = ''): string
    {
        $index = $this->getIndex();

        return $prefix . (is_array($index) ? implode('_', $index) : $index);
    }

    /**
     * @return string|array
     *
     * @throws EnvironmentNotFoundException
     */
    private function getIndex()
    {
        $environment = $this->environmentHelper->getEnvironment();

        if ($environment === null) {
            throw new EnvironmentNotFoundException();
        }

        $prefixes = explode('|', $this->indexPrefix);
        $out = [];
        foreach ($prefixes as $prefix) {
            $out[] = $prefix . $environment;
        }
        if (!empty($out)) {
            return $out;
        }
        return $this->indexPrefix . $environment;
    }

    /**
     * @return string
     */
    private function getFirstIndex()
    {
        $aliases = $this->getIndex();
        if (is_array($aliases) && count($aliases) > 0) {
            $aliases = $aliases[0];
        }

        return array_keys($this->client->indices()->getAlias([
            'index' => $aliases,
        ]))[0];
    }

    public function getCacheResponse(array $cacheKey, ?string $type, callable $function)
    {
        if ($type === null) {
            return $function();
        }
        $cacheHash = \sha1(\json_encode($cacheKey));

        $cachedHierarchy = $this->cache->getItem($cacheHash);
        $lastUpdate = $this->getLastChangeDate($type);

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
