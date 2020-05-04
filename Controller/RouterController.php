<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Cache\CacheHelper;
use EMS\ClientHelperBundle\Helper\Request\Handler;
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

    public function __construct(Handler $handler, Environment $templating)
    {
        $this->handler = $handler;
        $this->templating = $templating;
    }

    public function handle(Request $request): Response
    {
        $result = $this->handler->handle($request);

        $response = new Response($this->templating->render($result['template'], $result['context']));
        CacheHelper::makeResponseCacheable($request, $response);
        return $response;
    }

    public function redirect(Request $request)
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = json_decode($json, true);

        return new RedirectResponse($data['url'], ($data['status'] ?? 302));
    }
}
