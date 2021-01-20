<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Helper\Builder;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequestManager;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for client builders.
 *
 * @see \EMS\ClientHelperBundle\Helper\Routing\RoutingBuilder
 * @see \EMS\ClientHelperBundle\Helper\Templating\TemplateBuilder
 * @see \EMS\ClientHelperBundle\Helper\Translation\TranslationBuilder
 */
abstract class AbstractBuilder
{
    protected ClientRequest $clientRequest;
    protected LoggerInterface $logger;

    public function __construct(ClientRequestManager $manager, LoggerInterface $logger)
    {
        $this->clientRequest = $manager->getDefault();
        $this->logger = $logger;
    }
}
