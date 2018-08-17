<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\Helper\Twig\TwigLoader;
use EMS\CommonBundle\Common\EMSLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class RouterController
{
    /**
     * @var ClientRequestManager
     */
    private $manager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var \Twig_Environment
     */
    private $templating;

    /**
     * @param ClientRequestManager $manager
     * @param RouterInterface      $router
     * @param \Twig_Environment    $templating
     */
    public function __construct(ClientRequestManager $manager, RouterInterface $router, \Twig_Environment $templating)
    {
        $this->manager = $manager;
        $this->router = $router;
        $this->templating = $templating;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        try {
            $route = $this->getRoute($request);

            $client = $this->manager->getDefault();
            $context = ['trans_default_domain' => $client->getCacheKey()];

            $document = $this->getDocument($client, $request, $route);
            $template = $this->getTemplate($route, $document);

            if ($document) {
                $context['document'] = $document;
                $context['source'] = $document['_source'];
                $context['emsLink'] = EMSLink::fromDocument($document);
            }

            return new Response($this->templating->render($template, $context), 200);
        } catch (SingleResultException $e) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param Request $request
     *
     * @return null|Route
     */
    private function getRoute(Request $request)
    {
        $name = $request->attributes->get('_route');
        $route = $this->router->getRouteCollection()->get($name);

        if (null === $route) {
            throw new NotFoundHttpException('ems route not found');
        }

        return $route;
    }

    /**
     * @param ClientRequest $client
     * @param Request       $request
     * @param Route         $route
     *
     * @return array|null
     *
     * @throws SingleResultException
     */
    public function getDocument(ClientRequest $client, Request $request, Route $route)
    {
        $query = $route->getOption('query');

        if (null === $query) {
            return null;
        }

        $pattern = '/%(?<parameter>(_|)[[:alnum:]]*)%/m';

        $json = preg_replace_callback($pattern, function ($match) use ($request) {
            return $request->get($match['parameter'], $match[0]);
        }, $query);

        return $client->searchOne($route->getOption('type'), json_decode($json, true));
    }

    /**
     * @param Route $route
     * @param array $document
     *
     * @return string
     */
    private function getTemplate(Route $route, array $document = null)
    {
        $template = $route->getOption('template');

        if (null === $document || substr($template, 0, 6) === TwigLoader::PREFIX) {
            return $template;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return TwigLoader::PREFIX . '/' . $propertyAccessor->getValue($document, '[_source]'.$template);
    }
}