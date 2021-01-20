<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * @see EMSClientHelperExtension::defineTwigLoader()
 */
final class TemplateLoader implements LoaderInterface
{
    private EnvironmentHelper $environmentHelper;
    private TemplateBuilder $templateBuilder;

    public function __construct(EnvironmentHelper $environmentHelper, TemplateBuilder $templateBuilder)
    {
        $this->environmentHelper = $environmentHelper;
        $this->templateBuilder = $templateBuilder;
    }

    public function getSourceContext($name): Source
    {
        $code = $this->templateBuilder->buildTemplate($this->getEnvironment(), new Template($name));

        return new Source($code, $name);
    }

    public function getCacheKey($name)
    {
        return \implode('_', ['twig', $this->getEnvironment()->getAlias(), $name]);
    }

    public function isFresh($name, $time)
    {
        return $this->templateBuilder->isFresh($this->getEnvironment(), new Template($name), $time);
    }

    public function exists($name): bool
    {
        if (null === $this->environmentHelper->getCurrentEnvironment()) {
            return false;
        }

        return Template::validate($name);
    }

    private function getEnvironment(): Environment
    {
        if (null === $environment = $this->environmentHelper->getCurrentEnvironment()) {
            throw new \RuntimeException('Can not load template without environment!');
        }

        return $environment;
    }
}
