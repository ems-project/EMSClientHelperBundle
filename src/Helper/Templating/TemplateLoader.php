<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Templating;

use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Environment\EnvironmentHelper;
use EMS\ClientHelperBundle\Helper\Local\LocalEnvironment;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * @see EMSClientHelperExtension::defineTwigLoader()
 */
final class TemplateLoader implements LoaderInterface
{
    private EnvironmentHelper $environmentHelper;
    private TemplateBuilder $builder;

    public function __construct(EnvironmentHelper $environmentHelper, TemplateBuilder $templateBuilder)
    {
        $this->environmentHelper = $environmentHelper;
        $this->builder = $templateBuilder;
    }

    public function getSourceContext($name): Source
    {
        $templateName = new TemplateName($name);

        if (null !== $localEnvironment = $this->getLocalEnvironment()) {
            $localTemplate = $localEnvironment->getTemplateFile($templateName);

            return new Source($localTemplate->getCode(), $name, $localTemplate->getPath());
        }

        $template = $this->builder->buildTemplate($this->getEnvironment(), $templateName);

        return new Source($template->getCode(), $name);
    }

    public function getCacheKey($name)
    {
        return \implode('_', ['twig', $this->getEnvironment()->getAlias(), $name]);
    }

    public function isFresh($name, $time)
    {
        $templateName = new TemplateName($name);

        if (null !== $localEnvironment = $this->getLocalEnvironment()) {
            return $localEnvironment->getTemplateFile($templateName)->isFresh($time);
        }

        return $this->builder->isFresh($this->getEnvironment(), $templateName, $time);
    }

    public function exists($name): bool
    {
        if (null === $this->environmentHelper->getCurrentEnvironment()) {
            return false;
        }

        return TemplateName::validate($name);
    }

    private function getEnvironment(): Environment
    {
        if (null === $environment = $this->environmentHelper->getCurrentEnvironment()) {
            throw new \RuntimeException('Can not load template without environment!');
        }

        return $environment;
    }

    private function getLocalEnvironment(): ?LocalEnvironment
    {
        return $this->builder->getLocalEnvironment($this->getEnvironment());
    }
}
