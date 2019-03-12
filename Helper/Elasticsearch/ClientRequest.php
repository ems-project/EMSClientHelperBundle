<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\Exception\EnvironmentNotFoundException;
use EMS\ClientHelperBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use Psr\Log\LoggerInterface;
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
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $name;

    const OPTION_INDEX_PREFIX = 'index_prefix';

    /**
     * @param Client            $client
     * @param EnvironmentHelper $environmentHelper
     * @param LoggerInterface   $logger
     * @param string            $name
     * @param array             $options
     */
    public function __construct(
        Client $client,
        EnvironmentHelper $environmentHelper,
        LoggerInterface $logger,
        $name,
        array $options = []
    )
    {
        $this->client = $client;
        $this->environmentHelper = $environmentHelper;
        $this->logger = $logger;
        $this->options = $options;
        $this->indexPrefix = isset($options[self::OPTION_INDEX_PREFIX]) ? $options[self::OPTION_INDEX_PREFIX] : null;
        $this->name = $name;
    }

    /**
     * @param string $text
     * @param string $searchField
     *
     * @return array
     */
    public function analyze($text, $searchField)
    {
        $this->logger->debug('ClientRequest : analyze {text} with {field}', ['text' => $text, 'field' => $searchField]);
        $out = [];
        preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $text, $out);
        $words = $out[0];

        $withoutStopWords = [];
        $params = [
            'index' => $this->getFirstIndex(),
            'field' => $searchField,
            'text' => ''
        ];
        foreach ($words as $word) {
            $params['text'] = $word;
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

    /**
     * @param string $emsKey
     * @param string $childrenField
     *
     * @return string|null
     */
    public function getAllChildren($emsKey, $childrenField)
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

    public function getHierarchy(string $emsKey, string $childrenField, int $depth = null, array $sourceFields = [], string $activeChild): ?HierarchicalStructure
    {
        $this->logger->debug('ClientRequest : getHierarchy for {emsKey}', ['emsKey' => $emsKey]);
        $item = $this->getByEmsKey($emsKey, $sourceFields);

        if (empty($item)) {
            return null;
        }

        $out = new HierarchicalStructure($item['_type'], $item['_id'], $item['_source'], ($item['_id'] === $activeChild ? true : false));

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

    public function getLastChangeDate(string $type): \DateTime
    {
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
        $this->logger->debug('ClientRequest : search for {type}', ['type' => $type, 'body' => $body, 'index' => $this->getIndex()]);

        $arguments = [
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
            'size' => $size,
            'from' => $from
        ];

        if (!empty($sourceExclude)) {
            $arguments['_source_exclude'] = $sourceExclude;
        }

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

    /**
     * @param string $type
     * @param array  $parameters
     *
     * @return string|null
     */
    public function searchOneBy($type, array $parameters)
    {
        $this->logger->debug('ClientRequest : searchOneBy for type {type}', ['type' => $type]);

        $result = $this->searchBy($type, $parameters, 0, 1);

        if ($result['hits']['total'] == 1) {
            return $result['hits']['hits'][0];
        }

        return false;
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

        if($environment === null) {
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
        $indexes = $this->getIndex();
        if (is_array($indexes) && count($indexes) > 0) {
            return $indexes[0];
        }
        return $indexes;
    }
}
