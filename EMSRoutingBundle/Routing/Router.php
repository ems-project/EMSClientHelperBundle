<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Routing;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig\TemplateLoader;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class Router
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ClientRequest
     */
    private $client;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @param ClientRequest   $clientRequest
     * @param RouterInterface $router
     * @param EngineInterface $templating
     */
    public function __construct(
        ClientRequest $clientRequest,
        RouterInterface $router,
        EngineInterface $templating
    ) {
        $this->router = $router;
        $this->client = $clientRequest;
        $this->templating = $templating;
    }

    /**
     * Handle ems routes
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $route = $this->getRoute($request);
        $type = $route->getOption('type');

        try {
            $body = $this->createSearchBody($request, $route);
            $document = $this->client->searchOne($type, $body);
            $template = $this->getTemplate($route, $document);

            $content = $this->templating->render($template, [
                'source' => $document['_source']
            ]);

            return new Response($content, 200);
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
     * Replaces all %values% parameters in the route query string,
     * with request attributes.
     *
     * @param Request $request
     * @param Route   $route
     *
     * @return array
     */
    private function createSearchBody(Request $request, Route $route)
    {
        $pattern = '/%(?<parameter>(_|)[[:alnum:]]*)%/m';

        $json = preg_replace_callback($pattern, function ($match) use ($request) {
            return $request->get($match['parameter'], $match[0]);
        }, $route->getOption('query'));

        return json_decode($json, true);
    }

    /**
     * @param Route $route
     * @param array $document
     *
     * @return string
     */
    private function getTemplate(Route $route, array $document)
    {
        $template = $route->getOption('template');

        if (substr($template, 0, 6) === TemplateLoader::PREFIX) {
            return $template;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return TemplateLoader::PREFIX . '/' . $propertyAccessor->getValue($document, '[_source]'.$template);
    }
}