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
     * @param ClientRequestManager $clientRequestManager
     * @param array                $locales
     */
    public function __construct(ClientRequestManager $clientRequestManager, array $locales)
    {
        $this->clientRequestManager = $clientRequestManager;
        $this->locales = $locales;
    }

    /**
     * @param Request $request
     * @param string  $template
     *
     * @return Response
     */
    public function view(Request $request, string $template)
    {
        return $this->render($template, [
            'destination' => $request->get('destination', ''),
            'locales' => $this->locales,
            'trans_default_domain' => $this->clientRequestManager->getDefault()->getCacheKey(),
        ]);
    }
}
