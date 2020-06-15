<?php

namespace EMS\ClientHelperBundle\Helper\Asset;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Storage\NotFoundException;
use EMS\CommonBundle\Storage\Processor\Config;
use EMS\CommonBundle\Storage\Processor\Processor;
use EMS\CommonBundle\Twig\RequestRuntime;
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
    /** @var RequestRuntime  */
    private $requestRuntime;

    public function __construct(Processor $processor, ClientRequestManager $clientRequestManager, UrlGeneratorInterface $urlGenerator, RequestRuntime $requestRuntime)
    {
        $this->processor = $processor;
        $this->clientRequest = $clientRequestManager->getDefault();
        $this->urlGenerator = $urlGenerator;
        $this->requestRuntime = $requestRuntime;
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
            return $this->processor->getResponse($request, $processor, $assetHash, $configHash);
        }

        return new Response('Deprecated use ems_asset twig filter', 500);
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

        $options = array_merge($this->getOptions($processor), $options);

        return $this->requestRuntime->assetPath([
            EmsFields::CONTENT_FILE_HASH_FIELD => $assetHash,
            EmsFields::CONTENT_FILE_NAME_FIELD => 'filename',
            EmsFields::CONTENT_MIME_TYPE_FIELD => 'image/png',
        ], $options);
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
