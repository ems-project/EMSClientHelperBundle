<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Request;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class LocaleHelper
{
    private RouterInterface $router;
    /** @var string[] */
    private array$locales;

    /**
     * @param string[] $locales
     */
    public function __construct(RouterInterface $router, array $locales)
    {
        $this->router = $router;
        $this->locales = $locales;
    }

    public function redirectMissingLocale(Request $request): RedirectResponse
    {
        $destination = $request->getPathInfo();

        if ('' === $destination || '/' === $destination) {
            $destination = null;
        }

        if ($request->cookies->has('_locale')) {
            $url = $request->getUriForPath('/'.$request->cookies->get('_locale').$destination);
        } elseif (1 === \count($this->locales)) {
            $url = $request->getUriForPath('/'.$this->locales[0].$destination);
        } else {
            $url = $this->router->generate('emsch_language_selection', ['destination' => $destination]);
        }

        return new RedirectResponse($url);
    }

    /**
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
     * @return string|false
     */
    private function getLocaleFromUri(string $uri)
    {
        $regex = \sprintf('/^\/(?P<locale>%s).*$/', \implode('|', $this->locales));
        \preg_match($regex, $uri, $matches);

        return isset($matches['locale']) ? $matches['locale'] : false;
    }
}
