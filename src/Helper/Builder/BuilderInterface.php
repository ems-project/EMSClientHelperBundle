<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Builder;

use EMS\ClientHelperBundle\Helper\Environment\Environment;

interface BuilderInterface
{
    public function buildFiles(Environment $environment, string $directory): void;
}
