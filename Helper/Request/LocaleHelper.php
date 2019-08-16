<?php

namespace EMS\ClientHelperBundle\Helper\Request;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class LocaleHelper
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $locales;

    /**
     * @param RouterInterface $router
     * @param array           $locales
     */
    public function __construct(RouterInterface $router, array $locales)
    {
        $this->router = $router;
        $this->locales = $locales;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function redirectMissingLocale(Request $request)
    {
        $session = $request->getSession();
        $destination = $request->getPathInfo();

        if ('' === $destination || '/' === $destination) {
            $destination = null;
        }

        if ($session->has('_locale')) {
            $url = $request->getUriForPath('/' . $session->get('_locale') . $destination);
        } elseif (1 === count($this->locales)) {
            $url = $request->getUriForPath('/' . $this->locales[0] . $destination);
        } else {
            $url = $this->router->generate('emsch_language_selection', ['destination' => $destination]);
        }

        return new RedirectResponse($url);
    }

    /**
     * @param Request $request
     *
     * @return string|false
     */
    public function getLocale(Request $request)
    {
        $locale = $request->attributes->get('_locale', false);

        if ($locale) {
            return $locale;
        }

        $localeUri = $this->getLocaleFromUri($request->getPathInfo());

        if ($localeUri) {
            $request->setLocale($localeUri);
            return $localeUri;
        }

        return false;
    }

    /**
     * @param string $uri
     *
     * @return string|false
     */
    private function getLocaleFromUri($uri)
    {
        $regex = sprintf('/^\/(?P<locale>%s).*$/', implode('|', $this->locales));
        preg_match($regex, $uri, $matches);

        return (isset($matches['locale']) ? $matches['locale'] : false);
    }
}
