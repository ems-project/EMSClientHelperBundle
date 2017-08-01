<?php

namespace EMS\ClientHelperBundle\Translation;

use Elasticsearch\Client;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

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
        
        $param = [
            'preference' => '_primary', //http://stackoverflow.com/questions/10836142/elasticsearch-duplicate-results-with-paging
            'from' => 0,
            'size' => 0,
            'index' => $this->prefix.$domain,
            'type' => $this->type,
        ];
        
        $result = $this->client->search($param);
        $total = $result["hits"]["total"];
        
        $param['size'] = $pageSize;
        while($param['from'] < $total){
            $result = $this->client->search($param);
            $messages = [];
            foreach ($result["hits"]["hits"] as $data){
                if(isset($data['_source']['label_'.$locale])){
                    $messages[$data['_source']['key']] = $data['_source']['label_'.$locale];	
                }
            }
            $catalogue->add($messages, $this->project.'_'.$domain);
            $param['from'] += $pageSize;
        }

        return $catalogue;
    }
}
