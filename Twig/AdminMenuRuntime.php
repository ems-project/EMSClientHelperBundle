<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Request\RequestHelper;
use EMS\CommonBundle\Common\EMSLink;
use Twig\Extension\RuntimeExtensionInterface;

class AdminMenuRuntime implements RuntimeExtensionInterface
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;

    /**
     * RequestHelperRuntime constructor.
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param EMSLink|string $emsLink
     *
     * @return string
     */
    public function showAdminMenu($emsLink): string
    {
        $backend = $this->requestHelper->getBackend();

        if (!$backend) {
            return '';
        }

        if (!$emsLink instanceof EMSLink) {
            $emsLink = EMSLink::fromString($emsLink);
        }

        return vsprintf('data-ems-type="%s" data-ems-key="%s" data-ems-url="%s"', [
            $emsLink->getContentType(),
            $emsLink->getOuuid(),
            $backend
        ]);
    }
}