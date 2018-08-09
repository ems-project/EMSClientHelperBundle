<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Service\LanguageSelectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageSelectionController extends AbstractController
{
    /**
     * @var LanguageSelectionService
     */
    private $service;

    /**
     * @var string
     */
    private $template;

    /**
     * @param LanguageSelectionService $service
     * @param string                   $template
     */
    public function __construct(LanguageSelectionService $service, string $template)
    {
        $this->service = $service;
        $this->template = $template;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function select(Request $request)
    {
        return $this->render($this->template, [
            'destination' => $request->get('destination', ''),
            'selections' => $this->service->getLanguageSelections(),
            'trans_default_domain' => $this->service->getClient()->getNameEnv(),
        ]);
    }
}
