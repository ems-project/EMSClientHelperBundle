<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Routing\Url\Transformer;
use Twig\Extension\RuntimeExtensionInterface;

class RoutingRuntime implements RuntimeExtensionInterface
{
    /**
     * @var Transformer
     */
    private $transformer;

    /**
     * @param Transformer $transformer
     */
    public function __construct(Transformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * @param string $relativePath
     * @param string $path
     * @param array  $parameters
     *
     * @return string
     */
    public function createUrl($relativePath, $path, array $parameters = [])
    {
        $url = $this->transformer->getGenerator()->createUrl($relativePath, $path);

        if ($parameters) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }

    /**
     * @param string $content
     * @param string $locale
     * @param string $baseUrl
     *
     * @return null|string|string[]
     */
    public function transform($content, $locale = null, $baseUrl = null)
    {
        return $this->transformer->transform($content, $locale, $baseUrl);
    }
}
