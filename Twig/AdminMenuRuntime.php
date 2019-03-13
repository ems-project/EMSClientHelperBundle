<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\CommonBundle\Common\EMSLink;
use Twig\Extension\RuntimeExtensionInterface;

class AdminMenuRuntime implements RuntimeExtensionInterface
{
    /**
     * @var EnvironmentHelper
     */
    private $environmentHelper;

    /**
     * @param EnvironmentHelper $environmentHelper
     */
    public function __construct(EnvironmentHelper $environmentHelper)
    {
        $this->environmentHelper = $environmentHelper;
    }

    /**
     * @param EMSLink|string $emsLink
     *
     * @return string
     */
    public function showAdminMenu($emsLink): string
    {
        $backend = $this->environmentHelper->getBackend();

        if (!$backend) {
            return '';
        }

        if (!$emsLink instanceof EMSLink) {
            $emsLink = EMSLink::fromText($emsLink);
        }

        return vsprintf('data-ems-type="%s" data-ems-key="%s" data-ems-url="%s"', [
            $emsLink->getContentType(),
            $emsLink->getOuuid(),
            $backend
        ]);
    }
}
