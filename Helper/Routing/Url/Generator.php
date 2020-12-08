<?php

namespace EMS\ClientHelperBundle\Helper\Routing\Url;

use EMS\CommonBundle\Common\EMSLink;
use Symfony\Component\Routing\RouterInterface;

class Generator
{
    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * @var string
     */
    private $phpApp = '';

    /**
     * @var array
     */
    private $relativePaths;

    /**
     * Regex for getting the base URL without the phpApp
     * So we can relative link to other applications.
     */
    const REGEX_BASE_URL = '/^(?P<baseUrl>\/.*?)(?:(?P<phpApp>\/[\-_A-Za-z0-9]*.php)|\/|)$/i';

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

    /**
     * @param string $relativePath
     * @param string $path
     *
     * @return string
     */
    public function createUrl($relativePath, $path)
    {
        return $this->baseUrl.$relativePath.$this->phpApp.$path;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function prependBaseUrl(EMSLink $emsLink, $url)
    {
        $path = $this->getRelativePath($emsLink->getContentType());

        return $this->baseUrl.$path.$this->phpApp.$url;
    }

    /**
     * @param string $contentType
     *
     * @return string
     */
    private function getRelativePath($contentType)
    {
        foreach ($this->relativePaths as $value) {
            if (\preg_match($value['regex'], $contentType)) {
                return $value['path'];
            }
        }

        return '';
    }
}
