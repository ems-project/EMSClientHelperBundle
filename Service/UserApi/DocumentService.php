<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

final class DocumentService extends UserApiService
{
    public function getDocument(string $contentType, string $ouuid, array $context): array
    {
        $client = $this->createClient(['X-Auth-Token' => $context['authToken']]);
        $response = $client->get(\sprintf('/api/data/%s/%s', $contentType, $ouuid));

        return \json_decode($response->getBody()->getContents(), true);
    }

    public function storeDocument(string $contentType, array $context): array
    {
        $endpoint = \sprintf('api/data/%s/draft', $contentType);
        return $this->actionDocument($contentType, $context, $endpoint);
    }

    public function updateDocument(string $contentType, string $ouuid, array $context): array
    {
        $endpoint = \sprintf('/api/data/%s/replace/%s', $contentType, $ouuid);
        return $this->actionDocument($contentType, $context, $endpoint);
    }

    public function mergeDocument(string $contentType, string $ouuid, array $context): array
    {
        $endpoint = \sprintf('/api/data/%s/merge/%s', $contentType, $ouuid);
        return $this->actionDocument($contentType, $context, $endpoint);
    }

    private function actionDocument(string $contentType, array $context, string $endpoint): array
    {
        $client = $this->createClient(['X-Auth-Token' => $context['authToken']]);

        $draftResponse = $client->post(
            $endpoint,
            ['body' => \json_encode($context['body'])]
        );

        $draft = \json_decode($draftResponse->getBody()->getContents(), true);

        $finalizeUrl = \sprintf('api/data/%s/finalize/%d', $contentType, $draft['revision_id']);
        $finalizeResponse = $client->post($finalizeUrl);

        return \json_decode($finalizeResponse->getBody()->getContents(), true);
    }
}
