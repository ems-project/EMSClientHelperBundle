<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Search\Manager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends AbstractController
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var array
     */
    private $locales;

    /**
     * @param Manager $manager
     * @param array   $locales
     */
    public function __construct(Manager $manager, array $locales)
    {
        $this->manager = $manager;
        $this->locales = $locales;
    }

    /**
     * @param Request $request
     * @param string  $template
     *
     * @return Response
     *
     * @throws \EMS\ClientHelperBundle\Exception\EnvironmentNotFoundException
     */
    public function results(Request $request, string $template)
    {
        $search = $this->manager->search($request);

        return $this->render($template, array_merge([
            'trans_default_domain' => $this->manager->getClientRequest()->getCacheKey(),
            'language_routes' => $this->getLanguageRoutes($request),
        ], $search));
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getLanguageRoutes(Request $request)
    {
        $routes = [];

        foreach ($this->locales as $locale) {
            $routes[$locale] = $this->generateUrl('emsch_search', array_merge(['_locale' => $locale], $request->query->all()));
        }

        return $routes;
    }
}