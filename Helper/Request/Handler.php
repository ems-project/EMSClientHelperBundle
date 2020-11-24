<?php

namespace EMS\ClientHelperBundle\Helper\Request;

use EMS\ClientHelperBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Twig\TwigLoader;
use EMS\CommonBundle\Common\EMSLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Route as SymfonyRoute;

class Handler
{
    /** @var ClientRequest */
    private $clientRequest;
    /** @var RouterInterface */
    private $router;

    public function __construct(ClientRequestManager $manager, RouterInterface $router)
    {
        $this->clientRequest = $manager->getDefault();
        $this->router = $router;
    }

    public function handle(Request $request): array
    {
        $route = $this->getRoute($request);
        $context = ['trans_default_domain' => $this->clientRequest->getCacheKey()];

        if (null !== $document = $this->getDocument($request, $route)) {
            $context['document'] = $document;
            $context['source'] = $document['_source'];
            $context['emsLink'] = EMSLink::fromDocument($document);
        }

        return [
            'template' => $this->getTemplate($request, $route, $document),
            'context' => $context,
        ];
    }

    private function getRoute(Request $request): SymfonyRoute
    {
        $name = $request->attributes->get('_route');
        $route = $this->router->getRouteCollection()->get($name);

        if (null === $route) {
            throw new NotFoundHttpException(sprintf('ems route "%s" not found', $name));
        }

        return $route;
    }

    /**
     * @return array{_id:string,_type:string,_source:array<mixed>}|null
     */
    public function getDocument(Request $request, SymfonyRoute $route): ?array
    {
        $query = $route->getOption('query');

        if (null === $query) {
            return null;
        }

        $pattern = '/%(?<parameter>(_|)[[:alnum:]]*)%/m';
        $json = preg_replace_callback($pattern, function ($match) use ($request) {
            return $request->get($match['parameter'], $match[0]);
        }, $query);

        $indexRegex = $route->getOption('index_regex');
        if ($indexRegex !== null) {
            $pattern = '/%(?<parameter>(_|)[[:alnum:]]*)%/m';
            $indexRegex = preg_replace_callback($pattern, function ($match) use ($request) {
                return $request->get($match['parameter'], $match[0]);
            }, $indexRegex);
        }


        try {
            return $this->clientRequest->searchOne($route->getOption('type'), json_decode($json, true), $indexRegex);
        } catch (SingleResultException $e) {
            throw new NotFoundHttpException();
        }
    }

    private function getTemplate(Request $request, SymfonyRoute $route, array $document = null): string
    {
        $template = $route->getOption('template');

        $pattern = '/%(?<parameter>(_|)[[:alnum:]]*)%/m';
        $template = preg_replace_callback($pattern, function ($match) use ($request) {
            return $request->get($match['parameter'], $match[0]);
        }, $template);

        if (null === $document || substr($template, 0, 6) === TwigLoader::PREFIX) {
            return $template;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return TwigLoader::PREFIX . '/' . $propertyAccessor->getValue($document, '[_source]' . $template);
    }
}
