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
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
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
        $clientRequest = $this->manager->getClientRequest();
        $queryString = $request->get('q', false);
        $facets = $request->get('f', []);
        $sortBy = $request->get('s', false);
        $page = $request->get('p', 0);

        return $this->render($template, [
            'trans_default_domain' => $clientRequest->getCacheKey(),
            'results' => $this->manager->search($queryString, $facets, $request->getLocale(), $sortBy, $page),
            'query' => $queryString,
            'sort' => $sortBy,
            'facets' => $facets,
            'page' => $page,
        ]);
    }
}
