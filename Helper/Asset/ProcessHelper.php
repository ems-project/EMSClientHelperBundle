<?php

namespace EMS\ClientHelperBundle\Helper\Asset;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Storage\Processor\Config;
use EMS\CommonBundle\Storage\Processor\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class ProcessHelper implements RuntimeExtensionInterface
{
    /** @var Processor */
    private $processor;
    /** @var ClientRequest */
    private $clientRequest;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(Processor $processor, ClientRequestManager $clientRequestManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->processor = $processor;
        $this->clientRequest = $clientRequestManager->getDefault();
        $this->urlGenerator = $urlGenerator;
    }

    public function process(Request $request, string $processor, string $assetHash, string $configHash = null): Response
    {
        if ($configHash) {
            return $this->processor->fromCache($request, $processor, $assetHash, $configHash);
        }

        return  $this->processor->createResponse($request, $processor, $assetHash, $this->getOptions($processor));
    }

    public function generate(string $processor, string $assetHash, array $options = [])
    {
        $options = array_merge($this->getOptions($processor), $options);
        $config = $this->processor->process($processor, $assetHash, $options);

        return $this->urlGenerator->generate('emsch_processor_asset', [
            'processor' => $config->getProcessor(),
            'hash' => $config->getAssetHash(),
            'configHash' => $config->getConfigHash(),
            'type' => $config->getMimeType(),
        ]);
    }

    private function getOptions(string $processor): array
    {
        if (null == $this->clientRequest->hasOption('asset_config_type')) {
            return [];
        }

        $configType = $this->clientRequest->getOption('[asset_config_type]');

        try {
            $document = $this->clientRequest->searchOne($configType, [
                'query' => [
                    'term' => [
                        '_identifier' => ['value' => $processor]
                    ]
                ]
            ]);

            $defaults = Config::getDefaults();
            // removes invalid options like _sha1, _finalized_by, ..
            return array_intersect_key($document['_source'] + $defaults, $defaults);
        } catch (\Exception $e) {
            return [];
        }
    }
}
