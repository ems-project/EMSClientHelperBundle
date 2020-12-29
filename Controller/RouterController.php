<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Cache\CacheHelper;
use EMS\ClientHelperBundle\Helper\Request\Handler;
use EMS\CommonBundle\Storage\Processor\Processor;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class RouterController
{
    /** @var Handler */
    private $handler;
    /** @var Environment */
    private $templating;
    /** @var Processor */
    private $processor;
    /** @var CacheHelper */
    private $cacheHelper;

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

    public function redirect(Request $request)
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = \json_decode($json, true);

        return new RedirectResponse($data['url'], ($data['status'] ?? 302));
    }

    public function asset(Request $request): Response
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = \json_decode($json, true);

        return $this->processor->getResponse($request, $data['hash'], $data['config'], $data['filename']);
    }

    public function makeResponse(Request $request): Response
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = \json_decode($json, true);

        $response = new Response();

        $response->setContent($data['content']);

        foreach ($data['headers'] as $key => $value) {
            $response->headers->add([
                $key => $value,
            ]);
        }

        return $response;
    }
}
