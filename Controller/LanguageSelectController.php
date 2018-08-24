<?php

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageSelectController extends AbstractController
{
    /**
     * @var ClientRequestManager
     */
    private $clientRequestManager;

    /**
     * @var array
     */
    private $locales;

    /**
     * @var string
     */
    private $template;

    const DEFAULT_TEMPLATE = '@EMSClientHelper/language_select.html.twig';

    /**
     * @param ClientRequestManager $clientRequestManager
     * @param array                $locales
     * @param string               $template
     */
    public function __construct(ClientRequestManager $clientRequestManager, array $locales, $template)
    {
        $this->clientRequestManager = $clientRequestManager;
        $this->locales = $locales;
        $this->template = $template ?: self::DEFAULT_TEMPLATE;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function view(Request $request)
    {
        return $this->render($this->template, [
            'destination' => $request->get('destination', ''),
            'locales' => $this->locales,
            'trans_default_domain' => $this->clientRequestManager->getDefault()->getCacheKey(),
        ]);
    }
}
