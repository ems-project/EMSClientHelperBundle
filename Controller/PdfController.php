<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Request\Handler;
use EMS\CommonBundle\Service\Pdf\PdfGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;

class PdfController
{
    /** @var Handler */
    private $handler;
    /** @var \Twig_Environment */
    private $templating;
    /** @var PdfGenerator */
    private $pdfGenerator;

    public function __construct(Handler $handler, Environment $templating, PdfGenerator $pdfGenerator)
    {
        $this->handler = $handler;
        $this->templating = $templating;
        $this->pdfGenerator = $pdfGenerator;
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $result = $this->handler->handle($request);
        $html = $this->templating->render($result['template'], $result['context']);

        return $this->pdfGenerator->getStreamedResponse($html);
    }
}
