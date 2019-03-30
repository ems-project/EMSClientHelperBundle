<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Asset\AssetHelperRuntime;
use EMS\ClientHelperBundle\Helper\Asset\ProcessHelper;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class HelperExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('emsch_routing', [RoutingRuntime::class, 'transform'], ['is_safe' => ['html']]),
            new TwigFilter('emsch_data', [ClientRequestRuntime::class, 'data'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('emsch_admin_menu', [AdminMenuRuntime::class, 'showAdminMenu'], ['is_safe' => ['html']]),
            new TwigFunction('emsch_route', [RoutingRuntime::class, 'createUrl']),
            new TwigFunction('emsch_search', [ClientRequestRuntime::class, 'search']),
            new TwigFunction('emsch_search_config', [ClientRequestRuntime::class, 'searchConfig']),
            new TwigFunction('emsch_assets', [AssetHelperRuntime::class, 'assets']),
            new TwigFunction('emsch_unzip', [AssetHelperRuntime::class, 'unzip']),
            new TwigFunction('emsch_process_asset', [ProcessHelper::class, 'generate']),
        ];
    }
}
