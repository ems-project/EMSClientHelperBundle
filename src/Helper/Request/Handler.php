<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Request;

use EMS\ClientHelperBundle\Contracts\Request\HandlerInterface;
use EMS\ClientHelperBundle\Exception\SingleResultException;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Templating\TemplateDocument;
use EMS\CommonBundle\Common\EMSLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouterInterface;

final class Handler implements HandlerInterface
{
    private ClientRequest $clientRequest;
    private RouterInterface $router;

    public function __construct(ClientRequestManager $manager, RouterInterface $router)
    {
        $this->clientRequest = $manager->getDefault();
        $this->router = $router;
    }

    /**
     * @return array{template: string, context: array<mixed>}
     */
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
            throw new NotFoundHttpException(\sprintf('ems route "%s" not found', $name));
        }

        return $route;
    }

    /**
     * @return array{_id: string, _type?: string, _source: array}|null
     */
    public function getDocument(Request $request, SymfonyRoute $route): ?array
    {
        $query = $route->getOption('query');

        if (null === $query) {
            return null;
        }

        $json = RequestHelper::replace($request, $query);

        $indexRegex = $route->getOption('index_regex');
        if (null !== $indexRegex) {
            $indexRegex = RequestHelper::replace($request, $indexRegex);
        }

        try {
            return $this->clientRequest->searchOne($route->getOption('type'), \json_decode($json, true), $indexRegex);
        } catch (SingleResultException $e) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @param ?array<mixed> $document
     */
    private function getTemplate(Request $request, SymfonyRoute $route, array $document = null): string
    {
        $template = $route->getOption('template');
        $template = RequestHelper::replace($request, $template ?? '');

        if (null === $document || TemplateDocument::PREFIX === \substr($template, 0, 6)) {
            return $template;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return TemplateDocument::PREFIX.'/'.$propertyAccessor->getValue($document, '[_source]'.$template);
    }
}
