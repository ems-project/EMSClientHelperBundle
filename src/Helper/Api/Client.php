<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Api;

use EMS\CommonBundle\Common\HttpClientFactory;
use GuzzleHttp\Client as HttpClient;
use Psr\Log\LoggerInterface;

final class Client
{
    private HttpClient $client;
    private string $key;
    private string $name;
    private LoggerInterface $logger;

    public function __construct(string $name, string $baseUrl, string $key, LoggerInterface $logger)
    {
        $this->name = $name;
        $this->key = $key;
        $this->client = HttpClientFactory::create($baseUrl, ['X-Auth-Token' => $this->key]);
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @deprecated
     *
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    public function createDraft(string $type, array $body, ?string $ouuid = null): array
    {
        @\trigger_error('Deprecated use the initNewDocument or initNewDraftRevision functions', E_USER_DEPRECATED);

        return $this->initNewDocument($type, $body, $ouuid);
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    public function initNewDocument(string $type, array $body, ?string $ouuid = null): array
    {
        if (null === $ouuid) {
            $url = \sprintf('api/data/%s/draft', $type);
        } else {
            $url = \sprintf('api/data/%s/draft/%s', $type, $ouuid);
        }

        $response = $this->client->post(
            $url,
            ['body' => \json_encode($body)]
        );

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    public function updateDocument(string $type, ?string $ouuid, array $body): array
    {
        $response = $this->client->post(
            \sprintf('/api/data/%s/replace/%s', $type, $ouuid),
            ['body' => \json_encode($body)]
        );

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<mixed>
     */
    public function finalize(string $type, string $revisionId): array
    {
        $response = $this->client->post(
            \sprintf('api/data/%s/finalize/%d', $type, $revisionId)
        );

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<mixed>
     */
    public function discardDraft(string $type, string $revisionId)
    {
        $response = $this->client->post(
            \sprintf('api/data/%s/discard/%d', $type, $revisionId)
        );

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<mixed>
     */
    public function postFile(\SplFileInfo $file, ?string $forcedFilename = null): array
    {
        $response = $this->client->post('api/file', [
            'multipart' => [
                [
                    'name' => 'upload',
                    'contents' => \fopen($file->getPathname(), 'r'),
                    'filename' => $forcedFilename ?? $file->getFilename(),
                ],
            ],
        ]);

        return \json_decode($response->getBody()->getContents(), true);
    }

    public function createFormVerification(string $value): ?string
    {
        try {
            $response = $this->client->post('/api/forms/verifications', [
                'json' => ['value' => $value],
            ]);

            $json = \json_decode($response->getBody()->getContents(), true);

            return isset($json['code']) ? $json['code'] : null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }
    }

    public function getFormVerification(string $value): ?string
    {
        try {
            $response = $this->client->get('/api/forms/verifications', [
                'query' => ['value' => $value],
            ]);

            $json = \json_decode($response->getBody()->getContents(), true);

            return isset($json['code']) ? $json['code'] : null;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }
    }
}
