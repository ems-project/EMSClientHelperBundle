<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class EmbedController extends AbstractController
{
    /** @var ClientRequest */
    private $clientRequest;

    public function __construct(ClientRequestManager $manager)
    {
        $this->clientRequest = $manager->getDefault();
    }

    public function renderHierarchyAction(string $template, string $parent, string $field, int $depth = null, array $sourceFields = [], array $args = [], ?string $cacheType = null): Response
    {
        $cacheKey = [
            'EMSCH_Hierarchy',
            $template,
            $parent,
            $field,
            $depth,
            $sourceFields,
            $args,
            $cacheType,
        ];

        return $this->clientRequest->getCacheResponse($cacheKey, $cacheType, function () use ($template, $parent, $field, $depth, $sourceFields, $args) {
            $hierarchy = $this->clientRequest->getHierarchy($parent, $field, $depth, $sourceFields, $args['activeChild'] ?? null);

            return $this->render($template, [
                'translation_domain' => $this->clientRequest->getCacheKey(),
                'args' => $args,
                'hierarchy' => $hierarchy,
            ]);
        });
    }

    public function renderBlockAction(string $searchType, array $body, string $template, array $args = [], int $from = 0, int $size = 10, ?string $cacheType = null, array $sourceExclude = []): Response
    {
        $cacheKey = [
            'EMSCH_Block',
            $searchType,
            $body,
            $template,
            $args,
            $from,
            $size,
            $cacheType,
            $sourceExclude,
        ];

        return $this->clientRequest->getCacheResponse($cacheKey, $cacheType, function () use ($searchType, $body, $template, $args, $from, $size, $sourceExclude) {
            $result = $this->clientRequest->search($searchType, $body, $from, $size, $sourceExclude);

            return $this->render($template, [
                'translation_domain' => $this->clientRequest->getCacheKey(),
                'args' => $args,
                'result' => $result,
            ]);
        });
    }
}
