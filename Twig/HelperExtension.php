<?php

namespace EMS\ClientHelperBundle\Twig;

use Twig\Extension\AbstractExtension;

class HelperExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('emsch_routing', [RoutingRuntime::class, 'transform'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('emsch_admin_menu', [RequestHelperRuntime::class, 'showAdminMenu'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('emsch_route', [RoutingRuntime::class, 'createUrl']),
        ];
    }
}
