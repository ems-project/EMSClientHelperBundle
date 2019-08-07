<?php

namespace EMS\ClientHelperBundle\Helper\Api;

use EMS\CommonBundle\Common\HttpClientFactory;
use GuzzleHttp\Client as HttpClient;

class Client
{
    /**
     * @var HttpClient
     */
    private $client;
    
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $name;
    
    /**
     * @param string $name
     * @param string $baseUrl
     * @param string $key
     */
    public function __construct($name, $baseUrl, $key)
    {
        $this->name = $name;
        $this->key = $key;
        $this->client = HttpClientFactory::create($baseUrl, ['X-Auth-Token' => $this->key]);
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @deprecated
     * @param string $type
     * @param array $body
     * @param ?string $ouuid
     * @return array [acknowledged, revision_id, ouuid, success]
     */
    public function createDraft($type, $body, $ouuid = null)
    {
        @trigger_error('Deprecated use the initNewDocument or initNewDraftRevision functions', E_USER_DEPRECATED);
        return $this->initNewDocument($type, $body, $ouuid);
    }

    /**
     * @param string $type
     * @param array $body
     * @param ?string $ouuid
     * @return array [acknowledged, revision_id, ouuid, success]
     */
    public function initNewDocument($type, $body, $ouuid = null)
    {
        if ($ouuid === null) {
            $url = sprintf('api/data/%s/draft', $type);
        } else {
            $url = sprintf('api/data/%s/draft/%s', $type, $ouuid);
        }

        $response = $this->client->post(
            $url,
            ['body' => \json_encode($body)]
        );

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $type
     * @param array $body
     * @param ?string $ouuid
     * @return array [acknowledged, revision_id, ouuid, success]
     */
    public function updateDocument($type, $ouuid, $body)
    {
        $response = $this->client->post(
            sprintf('/api/data/%s/replace/%s', $type, $ouuid),
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
     * @param string|null $forcedFilename
     * @return array [uploaded, fileName, url]
     */
    public function postFile(\SplFileInfo $file, ?string $forcedFilename = null) : array
    {
        $response = $this->client->post('api/file', [
            'multipart' => [
                [
                    'name'     => 'upload',
                    'contents' => fopen($file->getPathname(), 'r'),
                    'filename' => $forcedFilename ?? $file->getFilename(),
                ],
            ]
        ]);

        return \json_decode($response->getBody()->getContents(), true);
    }
}
