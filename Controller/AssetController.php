<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Asset\ProcessHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AssetController
{
    /** @var ProcessHelper */
    private $processHelper;

    public function __construct(ProcessHelper $processHelper)
    {
        $this->processHelper = $processHelper;
    }

    /**
     * @deprecated
     * @param Request $request
     * @param string $processor
     * @param string $hash
     * @param string|null $configHash
     * @return Response
     */
    public function process(Request $request, string $processor, string $hash, string $configHash = null): Response
    {
        @trigger_error("AssetController::process is deprecated use the ems_asset twig filter to generate the route", E_USER_DEPRECATED);

        return $this->processHelper->process($request, $processor, $hash, $configHash);
    }
}
