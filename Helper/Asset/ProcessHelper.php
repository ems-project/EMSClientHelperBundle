<?php

namespace EMS\ClientHelperBundle\Helper\Asset;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Storage\NotFoundException;
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

    /**
     * @deprecated 
     * @param Request $request
     * @param string $processor
     * @param string $assetHash
     * @param string|null $configHash
     * @return Response
     */
    public function process(Request $request, string $processor, string $assetHash, string $configHash = null): Response
    {
        @trigger_error("ProcessHelper::process is deprecated use the ems_asset twig filter to generate the route", E_USER_DEPRECATED);

        if ($configHash) {
            return $this->processor->fromCache($request, $processor, $assetHash, $configHash);
        }

        return  $this->processor->createResponse($request, $processor, $assetHash, $this->getOptions($processor));
    }

    /**
     * @deprecated
     * @param string $processor
     * @param string $assetHash
     * @param array $options
     * @return string
     */
    public function generate(string $processor, string $assetHash, array $options = [])
    {
        @trigger_error("ProcessHelper::generate is deprecated use the ems_asset twig filter to generate the route", E_USER_DEPRECATED);
        try {
            $options = array_merge($this->getOptions($processor), $options);
            $config = $this->processor->process($processor, $assetHash, $options);

            return $this->urlGenerator->generate('emsch_processor_asset', [
                'processor' => $config->getProcessor(),
                'hash' => $config->getAssetHash(),
                'configHash' => $config->getConfigHash(),
                'type' => $config->getMimeType(),
            ]);
        }
        catch (NotFoundException $e) {
            //TODO: this method should only generate the asset confgi and save it in the storage service
            return $this->urlGenerator->generate('emsch_processor_asset', [
                'processor' => $processor,
                'hash' => $assetHash,
                'configHash' => 'not_found',
                'type' => 'image/png',
            ]);
        }
    }

    /**
     * @deprecated
     * @param string $processor
     * @return array
     */
    private function getOptions(string $processor): array
    {
        @trigger_error("ProcessHelper::getOptions is deprecated use the ems_asset twig filter to generate the route", E_USER_DEPRECATED);
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
