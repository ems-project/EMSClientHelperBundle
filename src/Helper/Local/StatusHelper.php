<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Local;

use EMS\ClientHelperBundle\Helper\Builder\Builders;
use EMS\ClientHelperBundle\Helper\Local\ChangeList\ChangeList;
use Psr\Log\LoggerInterface;

final class StatusHelper
{
    private Builders $builders;

    public function __construct(Builders $builders)
    {
        $this->builders = $builders;
    }

    public function translations(LocalEnvironment $localEnvironment): ChangeList
    {


        $changeList = new ChangeList();

        $translations = $this->builders->translation()->getTranslations($localEnvironment->getEnvironment());




        return $changeList;

    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}