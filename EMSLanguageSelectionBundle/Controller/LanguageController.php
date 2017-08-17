<?php

namespace EMS\ClientHelperBundle\EMSLanguageSelectionBundle\Controller;

use EMS\ClientHelperBundle\EMSLanguageSelectionBundle\Service\LanguageService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class LanguageController extends Controller
{
    /**
     * @Route("/language_selection", name="language_selection")
     */
    public function languageSelectionAction(
        Request $request)
    {
        /** @var LanguageService $languageService */
        $languageService = $this->get('emsch.languageselection.language.service');
        return $this->render('EMSLanguageSelectionBundle::language_selection.html.twig', [
            'destination' => $request->get('destination', ''),
            'selections' => $languageService->getLanguageSelections(),
            'emsch_trans_domain' => $languageService->getTranslationDomain()
        ]);
    }
}
