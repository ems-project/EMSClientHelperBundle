<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Routing\Handler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RouterController
{
    /** @var Handler */
    private $handler;
    /** @var \Twig_Environment */
    private $templating;

    public function __construct(Handler $handler, \Twig_Environment $templating)
    {
        $this->handler = $handler;
        $this->templating = $templating;
    }

    public function handle(Request $request): Response
    {
        $result = $this->handler->handle($request);

        return new Response($this->templating->render($result['template'], $result['context']), 200);
    }

    public function redirect(Request $request)
    {
        $result = $this->handler->handle($request);
        $json = $this->templating->render($result['template'], $result['context']);

        $data = json_decode($json, true);

        return new RedirectResponse($data['url'], ($data['status'] ?? 302));
    }
}