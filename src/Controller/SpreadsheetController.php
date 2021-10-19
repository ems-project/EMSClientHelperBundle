<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller;

use EMS\ClientHelperBundle\Helper\Request\Handler;
use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Contracts\SpreadsheetGeneratorServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;

final class SpreadsheetController
{
    private Handler $handler;
    private Environment $templating;
    private SpreadsheetGeneratorServiceInterface $spreadsheetGenerator;

    public function __construct(Handler $handler, Environment $templating, SpreadsheetGeneratorServiceInterface $spreadsheetGenerator)
    {
        $this->handler = $handler;
        $this->templating = $templating;
        $this->spreadsheetGenerator = $spreadsheetGenerator;
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $result = $this->handler->handle($request);
        $config = Json::decode($this->templating->render($result['template'], $result['context']));

        return $this->spreadsheetGenerator->generateSpreadsheet($config);
    }
}
