<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Api;

use GuzzleHttp\Client;

class ApiClient
{
    /**
     * @var Client
     */
    private $client;
    
    /**
     * @var string
     */
    private $key;
    
    /**
     * @param string $baseUrl
     * @param string $key
     */
    public function __construct($baseUrl, $key)
    {
        $this->key = $key;
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => ['X-Auth-Token' => $this->key]
        ]);
    }
    
    /**
     * @param string $type
     * @param array  $body
     *
     * @return array [acknowledged, revision_id, ouuid, success]
     */
    public function createDraft($type, $body)
    {
        $response = $this->client->post(
            sprintf('api/data/%s/draft', $type),
            ['body' => \json_encode($body)] 
        );

        return \json_decode($response->getBody()->getContents(), true);
    }
    
    /**
     * Call createDraft for a new revisionId
     * 
     * @param string $type
     * @param string $revisionId
     *
     * @return array [acknowledged, ouuid, success]
     */
    public function finalize($type, $revisionId)
    {
        $response = $this->client->post(
            sprintf('api/data/%s/finalize/%d', $type, $revisionId)
        );
        
        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param \SplFileInfo $file
     *
     * @return array [uploaded, fileName, url]
     */
    public function postFile(\SplFileInfo $file)
    {
        $response = $this->client->post('api/file', [
            'multipart' => [
                [
                    'name'     => 'upload',
                    'contents' => fopen($file->getPathname(), 'r'),
                    'filename' => $file->getFilename(),
                ],
            ]
        ]);

        return \json_decode($response->getBody()->getContents(), true);
    }
}
