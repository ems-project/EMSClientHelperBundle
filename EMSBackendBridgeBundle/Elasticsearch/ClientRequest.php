<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch;

use Elasticsearch\Client;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity\HierarchicalStructure;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\RequestService;
use EMS\ClientHelperBundle\EMSWebDebugBarBundle\Entity\ElasticSearchLog;
use EMS\ClientHelperBundle\EMSWebDebugBarBundle\Logger\ClientHelperLogger;
use Exception;
use Psr\Log\LoggerInterface;

class ClientRequest
{
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var RequestService
     */
    private $requestService;
    
    /**
     * @var string
     */
    private $indexPrefix;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ClientHelperLogger
     */
    protected $clientHelperLogger;
    
    /**
     * @param Client         $client
     * @param RequestService $requestService
     * @param string         $indexPrefix
     * @param LoggerInterface         $logger
     */
    public function __construct(
        Client $client,
        RequestService $requestService,
        $indexPrefix,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->requestService = $requestService;
        $this->indexPrefix = $indexPrefix;
        $this->logger = $logger;
    }

    /**
     * @param ClientHelperLogger $clientHelperLogger
     */
    public function setClientHelperLogger(ClientHelperLogger $clientHelperLogger)
    {
        $this->clientHelperLogger = $clientHelperLogger;
    }
    
    
    private function log($function, $arguments)
    {
        if (! $this->clientHelperLogger) {
            return;
        }

        $log = new ElasticSearchLog($function, $arguments);
        $this->clientHelperLogger->logElasticsearch($log);
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
     * @param string $emsKey
     * @param string $childrenField
     * @param integer $depth
     * @param array $sourceFields
     *
     * @return HierarchicalStructure|null
     */
    public function getHierarchy($emsKey, $childrenField, $depth = null, $sourceFields = [])
    {
        $this->logger->debug('ClientRequest : getHierarchy for {emsKey}', ['emsKey' => $emsKey]);
        $item = $this->getByEmsKey($emsKey, $sourceFields);

        if (empty($item)) {
            return null;
        }
        
        $out = new HierarchicalStructure($item['_type'], $item['_id'], $item['_source']);

        if( $depth === null || $depth ) {
            if(isset($item['_source'][$childrenField]) && is_array($item['_source'][$childrenField])) {
                foreach($item['_source'][$childrenField] as $key) {
                    if ($key){
                        $child = $this->getHierarchy($key, $childrenField, ($depth === null? null : $depth-1), $sourceFields);
                        if($child){
                            $out->addChild($child);
                        }
                    }
                }
            }
        }
        return $out;
        
    }
    
    /**
     * @param string $emsLink
     *
     * @return string|null
     */
    public function getAllChildren($emsKey, $childrenField)
    {
        
        $this->logger->debug('ClientRequest : getAllChildren for {emsKey}', ['emsKey' => $emsKey]);
        $out = [$emsKey];
        $item = $this->getByEmsKey($emsKey);
        
        if(isset($item['_source'][$childrenField]) && is_array($item['_source'][$childrenField])) {
            
            foreach($item['_source'][$childrenField] as $key) {
                $out = array_merge($out, $this->getAllChildren($key, $childrenField));
            }
            
        }
        return $out;
        
    }
    
    /**
     * @param string $emsLink
     *
     * @return array|null
     */
    public function searchBy($type, $parameters, $from = 0, $size = 10)
    {
        $this->logger->debug('ClientRequest : searchBy for type {type}', ['type'=>$type, 'parameters' => $parameters]);
        $body = [
            'query' => [
                'bool' => [
                    'must' => [],
                ],
            ],
        ];
        
        foreach ($parameters as $id => $value){
            $body['query']['bool']['must'][] = [
                'term' => [
                    $id => [
                        'value' => $value,
                    ]
                 ]
            ];
        }
        
        
        $arguments = [
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
            'size' => $size,
            'from' => $from,
        ];

        $this->log('searchBy', $arguments);
        $result = $this->client->search($arguments);

        return $result;
    }
    
    /**
     * @param string $emsLink
     *
     * @return string|null
     */
    public function searchOneBy($type, $parameters)
    {
        $this->logger->debug('ClientRequest : searchOneBy for type {type}', ['type'=>$type, 'parameters' => $parameters]);
        $result = $this->searchBy($type, $parameters, 0, 1);
        if($result['hits']['total'] == 1) {
            return $result['hits']['hits'][0];
        }
        
        return false;
    }
    
    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->requestService->getLocale();
    }
    
    /**
     * @param string $type
     * @param string $id
     *
     * @return array
     */
    public function get($type, $id)
    {
        $this->logger->debug('ClientRequest : get {type}:{id}', ['type'=>$type, 'id' => $id]);

        $arguments = [
            'index' => $this->getIndex(),
            'type' => $type,
            'id' => $id,
        ];

        $this->log('get', $arguments);
        $result = $this->client->get($arguments);

        return $result;
    }
    
    /**
     * @param string $type
     * @param string $id
     *
     * @return array
     */
    public function getByOuuids($type, $ouuids)
    {
        
        $this->logger->debug('ClientRequest : getByOuuids {type}:{id}', ['type'=>$type, 'id' => $ouuids]);

        $arguments = [
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => [
                'query' => [
                    'terms' => [
                        '_id' => $ouuids
                    ]
                ]
            ]
        ];

        $this->log('getByOuuids', $arguments);
        $result = $this->client->search($arguments);

        return $result;
    }
    
    
    public function getByEmsKey($emsLink, $sourceFields = []) {
        return $this->getByOuuid($this->getType($emsLink), $this->getOuuid($emsLink), $sourceFields);
    }
    
    /**
     * @param string $type
     * @param string $id
     * @param array  $sourceFields
     *
     * @return array | boolean
     */
    public function getByOuuid($type, $ouuid, $sourceFields = [], $source_exclude = [])
    {
        $this->logger->debug('ClientRequest : getByOuuid {type}:{id}', ['type'=>$type, 'id'=>$ouuid]);
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
        
        if(!empty($sourceFields)) {
            $arguments['_source'] = $sourceFields;
        }
        if(!empty($source_exclude)) {
            $arguments['_source_exclude'] = $source_exclude;
        }


        $this->log('getByOuuid', $arguments);
        $result = $this->client->search($arguments);

        if(isset($result['hits']['hits'][0])) {
            return $result['hits']['hits'][0];
        }
        return false;
    }
    
    /**
     * 
     * @param unknown $field
     */
    public function getFieldAnalyzer($field) {
        $this->logger->debug('ClientRequest : getFieldAnalyzer {field}', ['field'=>$field]);
        $info = $this->client->indices()->getFieldMapping([
            'index' => $this->getFirstIndex(),
            'field' => $field,
        ]);

        $analyzer = 'standard';
        while(is_array($info = array_shift($info)) ){
            if(isset($info['analyzer'])) {
                $analyzer = $info['analyzer'];
            }
            else if(isset($info['mapping'])) {
                $info = $info['mapping'];
            }
        }
        return $analyzer;
    }
    
    public function analyze($text, $searchField) {
        $this->logger->debug('ClientRequest : analyze {text} with {field}', ['text' => $text, 'field'=>$searchField]);
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
            if (isset($analyzed['tokens'][0]['token']))
            {
                $withoutStopWords[] = $word;
            }
        }
        return $withoutStopWords;
    }
    
    /**
     * @param string $type
     * @param array  $body
     * 
     * @return array
     */
    public function search($type, array $body, $from = 0, $size = 10, $sourceExclude=[])
    {
        
        $this->logger->debug('ClientRequest : search for {type}', ['type' => $type, 'body'=>$body, 'index'=>$this->getIndex()]);
        $arguments = [
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
            'size' => $size,
            'from' => $from
        ];
        
        if ($from > 0) {
//             $params['preference'] = '_primary';
        }
        
        if(!empty($sourceExclude)){
            $arguments['_source_exclude'] = $sourceExclude;
        }

        $this->log('search', $arguments);
        $result = $this->client->search($arguments);

        return $result;
    }
    
    /**
     * @param string $type
     * @param array  $body
     * 
     * @return array
     *
     * @throws Exception
     */
    public function searchOne($type, array $body)
    {
        $this->logger->debug('ClientRequest : searchOne for {type}', ['type' => $type, 'body'=>$body]);
        $search = $this->search($type, $body);
        
        $hits = $search['hits'];
        
        if (1 != $hits['total']) {
            throw new Exception(sprintf('expected 1 result, got %d', $hits['total']));
        }
        
        return $hits['hits'][0];
    }
    
    /**
     * @param string|array $type
     * @param array  $body
     * @param int    $size
     * 
     * //http://stackoverflow.com/questions/10836142/elasticsearch-duplicate-results-with-paging
     */
    public function searchAll($type, array $body, $pageSize = 10)
    {
        $this->logger->debug('ClientRequest : searchAll for {type}', ['type' => $type, 'body'=>$body]);
        $arguments = [
            'preference' => '_primary', //see function description
            //TODO: should be replace by an order by _ouid (in case of insert in the index the pagination will be inconsistent)
            'from' => 0,
            'size' => 0,
            'index' => $this->getIndex(),
            'type' => $type,
            'body' => $body,
        ];

        $this->log('searchAll', $arguments);
        $totalSearch = $this->client->search($arguments);

        $total = $totalSearch["hits"]["total"];
        
        $results = [];
        $arguments['size'] = $pageSize;
        
        while($arguments['from'] < $total){
            $this->log('searchAll', $arguments);
            $search = $this->client->search($arguments);
            
            foreach ($search["hits"]["hits"] as $document){
                $results[] = $document;
            }
            
            $arguments['from'] += $pageSize;
        }
        
        return $results;
    }
    
    /**
     * @return string | array
     */
    private function getIndex()
    {
        $prefixes = explode('|', $this->indexPrefix);
        $out = [];
        foreach ($prefixes as $prefix) {
            $out[] = $prefix . $this->requestService->getEnvironment();
        }
        if(!empty($out)){
            return $out;
        }
        return $this->indexPrefix . $this->requestService->getEnvironment();
    }
    
    /**
     * @return string
     */
    private function getFirstIndex()
    {
        $indexes = $this->getIndex();
        if(is_array($indexes) && count($indexes) > 0){
            return $indexes[0];
        }
        return $indexes;
    }
}
