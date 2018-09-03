<?php

namespace EMS\ClientHelperBundle\Helper\Routing;

use Symfony\Component\Routing\RouteCollection;

class Router extends BaseRouter
{
    /**
     * @var array
     */
    private $locales;

    /**
     * @var array
     */
    private $templates;

    /**
     * @param array $locales
     * @param array $templates
     */
    public function __construct(array $locales, array $templates)
    {
        $this->locales = $locales;
        $this->templates = $templates;
    }

    /**
     * @inheritdoc
     */
    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = $this->buildCollection();
        }

        return $this->collection;
    }

    /**
     * @return RouteCollection
     */
    private function buildCollection(): RouteCollection
    {
        $collection = new RouteCollection();

        if (isset($this->templates['language']) && count($this->locales) > 1) {
            $langSelectConfig = new RouteConfig('language_selection', [
                'path' => '/language-selection',
                'controller' => 'emsch.controller.language_select::view',
                'defaults' => ['template' => $this->templates['language']]
            ]);
            $collection->add($langSelectConfig->getName(), $langSelectConfig->getRoute());
        }

        if (isset($this->templates['search'])) {
            $searchConfig = new RouteConfig('search', [
                'path' => '/{_locale}/search',
                'controller' => 'emsch.controller.search::results',
                'defaults' => ['template' => $this->templates['search']]
            ]);
            $collection->add($searchConfig->getName(), $searchConfig->getRoute());
        }

        return $collection;
    }
}