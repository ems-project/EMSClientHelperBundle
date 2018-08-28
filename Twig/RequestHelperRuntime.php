<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Request\RequestHelper;
use EMS\CommonBundle\Common\EMSLink;
use Twig\Extension\RuntimeExtensionInterface;

class RequestHelperRuntime implements RuntimeExtensionInterface
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;
    /**
     * @var ClientRequestManager
     */
    private $manager;

    /**
     * RequestHelperRuntime constructor.
     * @param RequestHelper $requestHelper
     * @param ClientRequestManager $manager
     */
    public function __construct(RequestHelper $requestHelper, ClientRequestManager $manager)
    {
        $this->requestHelper = $requestHelper;
        $this->manager = $manager;
    }

    /**
     * @param $type
     * @param array $body
     * @param int $from
     * @param int $size
     * @param array $sourceExclude
     * @return array
     */
    public function search($type, array $body, $from = 0, $size = 10, array $sourceExclude = [])
    {
        $client = $this->manager->getDefault();
        return $client->search($type, $body, $from, $size, $sourceExclude);
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