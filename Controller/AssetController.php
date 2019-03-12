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

    public function process(Request $request, string $processor, string $hash, string $configHash = null): Response
    {
        return $this->processHelper->process($request, $processor, $hash, $configHash);
    }
}
