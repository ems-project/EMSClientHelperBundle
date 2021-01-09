<?php

namespace EMS\ClientHelperBundle\Helper\Request;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use EMS\ClientHelperBundle\Helper\Twig\TwigException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

class ExceptionHelper
{
    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @var ClientRequestManager
     */
    private $manager;

    /**
     * @var string
     */
    private $template;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param string $template
     */
    public function __construct(\Twig\Environment $twig, ClientRequestManager $manager, bool $debug, string $template = null)
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
        if (null === $this->template || $this->debug) {
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

    /**
     * @return string
     */
    private function getTemplate(string $code)
    {
        $customCodeTemplate = \str_replace('{code}', $code, $this->template);

        if ($this->templateExists($customCodeTemplate)) {
            return $customCodeTemplate;
        }

        $errorTemplate = \str_replace('{code}', '', $this->template);

        if ($this->templateExists($errorTemplate)) {
            return $errorTemplate;
        }

        throw new TwigException(\sprintf('template "%s" does not exists', $errorTemplate));
    }

    /**
     * @return bool
     */
    protected function templateExists(string $template)
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
