<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Cache\CacheHelper;
use EMS\ClientHelperBundle\Helper\Request\Handler;
use EMS\CommonBundle\Storage\Processor\Processor;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class RouterController
{
    private Handler $handler;
    private Environment $templating;
    private Processor $processor;
    private CacheHelper $cacheHelper;

    public function __construct(Handler $handler, Environment $templating, Processor $processor, CacheHelper $cacheHelper)
    {
        $this->handler = $handler;
        $this->templating = $templating;
        $this->processor = $processor;
        $this->cacheHelper = $cacheHelper;
    }

    public function handle(Request $request): Response
    {
        $result = $this->handler->handle($request);

        $response = new Response($this->templating->render($result['template'], $result['context']));
        $this->cacheHelper->makeResponseCacheable($request, $response);

        return $response;
    }

    public function redirect(Request $request): RedirectResponse
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = \json_decode($json, true);

        return new RedirectResponse($data['url'], $data['status'] ?? 302);
    }

    public function asset(Request $request): Response
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = \json_decode($json, true);

        if (\is_string($data['config'] ?? false)) {
            return $this->processor->getResponse($request, $data['hash'], $data['config'], $data['filename'], $data['immutable'] ?? false);
        }

        $config = $this->processor->configFactory($data['hash'], $data['config'] ?? []);

        return $this->processor->getStreamedResponse($request, $config, $data['filename'], $data['immutable'] ?? false);
    }

    public function makeResponse(Request $request): Response
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = \json_decode($json, true);

        if (false === $data) {
            throw new \RuntimeException('JSON is expected with at least a content field as a string');
        }

        if (!\is_string($data['content'] ?? null)) {
            throw new \RuntimeException('JSON requires at least a content field as a string');
        }

        $response = new Response();

        $response->setContent($data['content']);

        $headers = $data['headers'] ?? ['Content-Type' => 'text/plain'];

        if (!\is_array($headers)) {
            throw new \RuntimeException('Unexpected non-array headers parameter');
        }

        foreach ($headers as $key => $value) {
            $response->headers->add([
                $key => $value,
            ]);
        }

        return $response;
    }
}
