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
     * @param LanguageSelectionService $service
     */
    public function __construct(LanguageSelectionService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function selectAction(Request $request)
    {
        return $this->render('@EMSClientHelper/LanguageSelection/selection.html.twig', [
            'destination' => $request->get('destination', ''),
            'selections' => $this->service->getLanguageSelections(),
            'trans_default_domain' => $this->service->getClient()->getTranslationDomain(),
        ]);
    }
}
