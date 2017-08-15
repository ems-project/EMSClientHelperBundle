<?php

namespace EMS\ClientHelperBundle\Frontend\Twig;

use EMS\ClientHelperBundle\Elasticsearch\ClientRequest;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HelperExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('emsch_ouuid', array($this, 'getOuuid')),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getOuuid($emsLink)
    {
        return ClientRequest::getOuuid($emsLink);
    }

        /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emsch_helper';
    }
}
