<?php

namespace EMS\ClientHelperBundle\Helper\Request;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Twig\TwigException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ExceptionHelper
{
    private Environment $twig;
    private ClientRequestManager $manager;
    private string $template;
    private bool $debug;

    public function __construct(Environment $twig, ClientRequestManager $manager, bool $debug, string $template = '')
    {
        $this->twig = $twig;
        $this->manager = $manager;
        $this->debug = $debug;
        $this->template = $template;
    }

    /**
     * @return Response|false
     */
    public function renderError(FlattenException $exception)
    {
        if ('' === $this->template || $this->debug) {
            return false;
        }

        $code = $exception->getStatusCode();
        $template = $this->getTemplate($code);

        return new Response($this->twig->render($template, [
            'trans_default_domain' => $this->manager->getDefault()->getCacheKey(),
            'status_code' => $code,
            'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
            'exception' => $exception,
        ]));
    }

    private function getTemplate(int $code): string
    {
        $customCodeTemplate = \str_replace('{code}', \strval($code), $this->template);

        if ($this->templateExists($customCodeTemplate)) {
            return $customCodeTemplate;
        }

        $errorTemplate = \str_replace('{code}', '', $this->template);

        if ($this->templateExists($errorTemplate)) {
            return $errorTemplate;
        }

        throw new TwigException(\sprintf('template "%s" does not exists', $errorTemplate));
    }

    protected function templateExists(string $template): bool
    {
        try {
            $loader = $this->twig->getLoader();
            $loader->getSourceContext($template)->getCode();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
