<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Routing\Url;

use EMS\CommonBundle\Common\EMSLink;
use Symfony\Component\Routing\RouterInterface;

final class Generator
{
    private string $baseUrl = '';
    private string $phpApp = '';
    /** @var array<int, array{regex: string, path: string}> */
    private array $relativePaths;

    /**
     * Regex for getting the base URL without the phpApp
     * So we can relative link to other applications.
     */
    private const REGEX_BASE_URL = '/^(?P<baseUrl>\/.*?)(?:(?P<phpApp>\/[\-_A-Za-z0-9]*.php)|\/|)$/i';

    /**
     * @param array<int, array{regex: string, path: string}> $relativePaths
     */
    public function __construct(RouterInterface $router, array $relativePaths = [])
    {
        \preg_match(self::REGEX_BASE_URL, $router->getContext()->getBaseUrl(), $match);

        if (isset($match['baseUrl'])) {
            $this->baseUrl = $match['baseUrl'];
        }

        if (isset($match['phpApp'])) {
            $this->phpApp = $match['phpApp'];
        }

        $this->relativePaths = $relativePaths;
    }

    public function createUrl(string $relativePath, string $path): string
    {
        return $this->baseUrl.$relativePath.$this->phpApp.$path;
    }

    public function prependBaseUrl(EMSLink $emsLink, string $url): string
    {
        $url = \trim($url);
        $path = $this->getRelativePath($emsLink->getContentType());
        $baseUrl = $this->baseUrl.$path.$this->phpApp;

        if (\strlen($baseUrl) > 0 && 0 === \strpos($url, $baseUrl)) {
            return $url;
        }

        return $baseUrl.$url;
    }

    private function getRelativePath(string $contentType): string
    {
        foreach ($this->relativePaths as $value) {
            if (\preg_match($value['regex'], $contentType)) {
                return $value['path'];
            }
        }

        return '';
    }
}
