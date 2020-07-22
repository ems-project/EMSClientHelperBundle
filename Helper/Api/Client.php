<?php

namespace EMS\ClientHelperBundle\Helper\Api;

use EMS\CommonBundle\Common\HttpClientFactory;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

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

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string $name
     * @param string $baseUrl
     * @param string $key
     */
    public function __construct($name, $baseUrl, $key, LoggerInterface $logger)
    {
        $this->name = $name;
        $this->key = $key;
        $this->client = HttpClientFactory::create($baseUrl, ['X-Auth-Token' => $this->key]);
        $this->logger = $logger;
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
     * Call discardDraft for a revisionId
     *
     * @param string $type
     * @param string $revisionId
     *
     * @return array [acknowledged, ouuid, success]
     */
    public function discardDraft($type, $revisionId)
    {
        $response = $this->client->post(
            sprintf('api/data/%s/discard/%d', $type, $revisionId)
        );

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param \SplFileInfo $file
     * @param string|null $forcedFilename
     * @return array [uploaded, fileName, url]
     */
    public function postFile(\SplFileInfo $file, ?string $forcedFilename = null): array
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

    public function createFormVerification(string $value): ?int
    {
        try {
            $response = $this->client->post('/api/forms/verifications', [
                'json' => ['value' => $value],
            ]);

            $json = \json_decode($response->getBody()->getContents(), true);

            return isset($json['code']) ? (int) $json['code'] : null;
        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    public function getFormVerification(string $value): ?int
    {
        try {
            $response = $this->client->get('/api/forms/verifications', [
                'query' => ['value' => $value],
            ]);

            $json = \json_decode($response->getBody()->getContents(), true);

            return isset($json['code']) ? (int) $json['code'] : null;
        } catch (RequestException $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }
}
