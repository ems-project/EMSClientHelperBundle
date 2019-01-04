<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Storage\Processor\Config;
use EMS\CommonBundle\Storage\Processor\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetController
{
    /** @var Processor */
    private $processor;
    /** @var ClientRequest */
    private $clientRequest;

    public function __construct(Processor $processor, ClientRequestManager $clientRequestManager)
    {
        $this->processor = $processor;
        $this->clientRequest = $clientRequestManager->getDefault();
    }

    public function process(Request $request, string $processor, string $hash): Response
    {
        $doc = $this->getDocument($processor, $request->get('config_type', null));
        $config = new Config($processor, $doc);

        return $this->processor->createResponse($request, $config, $hash);
    }

    private function getDocument(string $processor, ?string $configType): array
    {
        if (null == $configType) {
            return [];
        }

        try {
            $document = $this->clientRequest->searchOne($configType, [
                'query' => [
                    'term' => [
                        'identifier' => ['value' => $processor]
                    ]
                ]
            ]);

            return $document['_source'];
        } catch (\Exception $e) {
            return [];
        }
    }
}
