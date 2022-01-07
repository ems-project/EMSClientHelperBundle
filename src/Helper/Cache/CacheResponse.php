<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Cache;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CacheResponse
{
    private Response $response;
    private string $content;

    public const HEADER_X_EMSCH_CACHE = 'X-emsch-cache';

    public function __construct(Response $response)
    {
        if ($response instanceof StreamedResponse) {
            throw new \RuntimeException('Stream responses are not cacheable!');
        }

        if (Response::HTTP_OK === $response->getStatusCode()) {
            throw new \RuntimeException('No 200 response');
        }

        $content = $response->getContent();
        if (!\is_string($content)) {
            throw new \RuntimeException('The response without content');
        }

        $this->response = $response;
        $this->content = $content;
    }

    public static function fromCache(CacheItemInterface $cacheItem): self
    {
        /** @var array{status: int, headers: array<mixed>, content: string} $data */
        $data = $cacheItem->get();
        $response = new Response($data['content'], $data['status'], $data['headers']);
        $response->headers->set(self::HEADER_X_EMSCH_CACHE, 'true');

        return new self($response);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @return array{status: int, headers: array<mixed>, content: string}
     */
    public function getData(): array
    {
        return [
            'status' => $this->response->getStatusCode(),
            'headers' => $this->response->headers->all(),
            'content' => $this->content,
        ];
    }
}
