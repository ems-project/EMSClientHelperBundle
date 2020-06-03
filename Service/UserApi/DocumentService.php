<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Service\UserApi;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DocumentService extends UserApiService
{
    /**
     * @return array<mixed>
     */
    public function getDocument(string $contentType, string $ouuid, Request $request): array
    {
        $client = $this->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);
        $response = $client->get(\sprintf('/api/data/%s/%s', $contentType, $ouuid));

        return \json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @return array<mixed>
     */
    public function storeDocument(string $contentType, Request $request): array
    {
        $endpoint = \sprintf('api/data/%s/draft', $contentType);
        return $this->actionDocument($contentType, $request, $endpoint);
    }

    /**
     * @return array<mixed>
     */
    public function updateDocument(string $contentType, string $ouuid, Request $request): array
    {
        $endpoint = \sprintf('/api/data/%s/replace/%s', $contentType, $ouuid);
        return $this->actionDocument($contentType, $request, $endpoint);
    }

    /**
     * @return array<mixed>
     */
    public function mergeDocument(string $contentType, string $ouuid, Request $request): array
    {
        $endpoint = \sprintf('/api/data/%s/merge/%s', $contentType, $ouuid);
        return $this->actionDocument($contentType, $request, $endpoint);
    }

    /**
     * @return array<mixed>
     */
    private function actionDocument(string $contentType, Request $request, string $endpoint): array
    {
        $client = $this->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);

        $body = $request->getContent();
        if (! \is_string($body)) {
            throw new NotFoundHttpException('JSON file not found');
        }

        $draftResponse = $client->post(
            $endpoint,
            \compact('body')
        );

        $draft = \json_decode($draftResponse->getBody()->getContents(), true);

        $finalizeUrl = \sprintf('api/data/%s/finalize/%d', $contentType, $draft['revision_id']);
        $finalizeResponse = $client->post($finalizeUrl);

        return \json_decode($finalizeResponse->getBody()->getContents(), true);
    }
}
