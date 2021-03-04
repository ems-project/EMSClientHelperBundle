<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use EMS\ClientHelperBundle\Helper\Environment\Environment;
use EMS\ClientHelperBundle\Helper\Local\Status\Status;
use Psr\Log\LoggerInterface;

final class StatusHelper
{
    private Builders $builders;
    private LoggerInterface $logger;

    public function __construct(Builders $builders, LoggerInterface $logger)
    {
        $this->builders = $builders;
        $this->logger = $logger;
    }

    public function routing(Environment $environment): Status
    {
        $status = new Status('Routing');
        $status->addBuilderDocuments($this->builders->routing()->getDocuments($environment));

        if (null === $contentType = $this->builders->routing()->getContentType($environment)) {
            return $status;
        }

        foreach ($environment->getLocal()->getRouting()->getData() as $name => $data) {
            $status->addItemLocal($name, $contentType->getName(), $data);
        }

        return $status;
    }

    public function templating(Environment $environment): Status
    {
        $status = new Status('Templating');
        $status->addBuilderDocuments($this->builders->templating()->getDocuments($environment));

        foreach ($environment->getLocal()->getTemplates() as $templateFile) {
            $mapping = $this->builders->templating()->getMappingConfig($templateFile->getContentType());
            $status->addItemLocal($templateFile->getName(), $templateFile->getContentType(), [
                ($mapping['name']) => $templateFile->getName(),
                ($mapping['code']) => $templateFile->getCode()
            ]);
        }

        return $status;
    }

    public function translation(Environment $environment): Status
    {
        $status = new Status('Translations');
        $status->addBuilderDocuments($this->builders->translation()->getDocuments($environment));

        if (null === $contentType = $this->builders->translation()->getContentType($environment)) {
            return $status;
        }

        foreach ($environment->getLocal()->getTranslations()->getData() as $name => $data) {
            $status->addItemLocal($name, $contentType->getName(), $data);
        }

        return $status;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
