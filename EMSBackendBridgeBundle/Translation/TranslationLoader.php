<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Translation;

use Elasticsearch\Client;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\ClearCacheService;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity\Translation;

class TranslationLoader implements LoaderInterface
{
    /**
     * @var Client
     */
    private $client;
        
    /**
     * @var string
     */
    private $project;
    
    /**
     * @var string
     */
    private $prefix;
    
    /**
     * @var string
     */
    private $type;
    
    /**
     * @param Client $client
     * @param string $project
     * @param string $prefix
     * @param string $type
     */
    public function __construct(Client $client, $project, $prefix, $type)
    {
        $this->client = $client;
        $this->project = $project;
        $this->prefix = $prefix;
        $this->type = $type;
    }
    
    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($locale);
        $pageSize = 100;

        $body = [
                'sort' => [
                    'modified_date' => [
                        'order' => 'desc',
                        'missing' => '_last'
                    ]
                ]
        ];
        
        //TODO: we can remove the 'preference' parameter if we add a second sort field on _uid in the body.
        $param = [
            'preference' => '_primary', //http://stackoverflow.com/questions/10836142/elasticsearch-duplicate-results-with-paging
            'from' => 0,
            'size' => 1,
            'index' => $this->prefix.$domain,
            'type' => $this->type,
            'body' => $body,
        	'_source' => 'label_*,modified_date,key',
        		
        ];
        
        
        $result = $this->client->search($param);
        $total = $result["hits"]["total"];

        $newestTranslation = new Translation(reset($result["hits"]["hits"])["_source"]);
        $date = $newestTranslation->getModifiedDate();
        if (!$date) {
            $date = (new \DateTime('19770209T160400'))->format('c');
        }
        
        if ($domain !== 'messages') {
            $key = ClearCacheService::TIMESTAMP_PREFIX . $domain;
            $message = [$key => $date];
            $catalogue->add($message, 'messages');
        }
        
        $param['size'] = $pageSize;
        while($param['from'] < $total){
            $result = $this->client->search($param);
            $messages = [];
            foreach ($result["hits"]["hits"] as $data){
            	if(isset($data['_source']['label_'.$locale]) && isset($data['_source']['key'])){
                    $messages[$data['_source']['key']] = $data['_source']['label_'.$locale];	
                }
            }
            $catalogue->add($messages, $this->project.'_'.$domain);
            $param['from'] += $pageSize;
        }

        return $catalogue;
    }
}
