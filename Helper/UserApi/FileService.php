<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\UserApi;

use EMS\ClientHelperBundle\Exception\UserApiResponseException;
use EMS\CommonBundle\Helper\EmsFields;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class FileService
{
    /** @var ClientFactory */
    private $client;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(ClientFactory $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $client = $this->client->createClient(['X-Auth-Token' => $request->headers->get('X-Auth-Token')]);

        $responses = [];
        foreach ($request->files as $file) {
            $responses = $this->upload($client, $file);
        }

        return JsonResponse::fromJsonString((\json_encode($responses)) ?: null);
    }

    /**
     * @return array<string>
     */
    private function upload(Client $client, UploadedFile $file): array
    {
        try {
            $response = $client->post('api/file', [
                'multipart' => [
                    [
                        'name' => 'upload',
                        'contents' => \fopen($file->getPathname(), 'r'),
                        'filename' => $file->getClientOriginalName(),
                    ],
                ]
            ]);

            $json = \json_decode($response->getBody()->getContents(), true);

            $success = 1 === $json['uploaded'];
            if (!$success) {
                throw UserApiResponseException::forFileUpload($response, $file);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return (isset($json)) ? $this->parseEmsResponse($json) : [];
    }

    /**
     * @param array<string> $response
     * @return array<string>
     */
    private function parseEmsResponse(array $response): array
    {
        //TODO: remove this hack once the ems back is returning the file hash as parameter
        if (!isset($response[EmsFields::CONTENT_FILE_HASH_FIELD_]) && isset($response['url'])) {
            $output_array = [];
            \preg_match('/\/data\/file\/view\/(?P<hash>.*)\?.*/', $response['url'], $output_array);
            if (isset($output_array['hash'])) {
                $response[EmsFields::CONTENT_FILE_HASH_FIELD_] = $output_array['hash'];
            }
        }

        return $response;
    }
}
