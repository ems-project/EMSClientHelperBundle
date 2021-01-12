<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LanguageSelectController extends AbstractController
{
    private ClientRequestManager $clientRequestManager;
    /** @var string[] */
    private array $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(ClientRequestManager $clientRequestManager, array $locales)
    {
        $this->clientRequestManager = $clientRequestManager;
        $this->locales = $locales;
    }

    public function view(Request $request, string $template): Response
    {
        return $this->render($template, [
            'destination' => $request->get('destination', ''),
            'locales' => $this->locales,
            'trans_default_domain' => $this->clientRequestManager->getDefault()->getCacheKey(),
        ]);
    }
}
