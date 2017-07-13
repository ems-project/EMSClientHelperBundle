<?php

namespace EMS\ClientHelperBundle\Twig;

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
        if (!strpos($emsLink, ':')) {
            return $emsLink;
        }
        
        list($contentType, $ouuid) = preg_split('/:/', $emsLink);
        
        return $ouuid;
    }

        /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emsch_helper';
    }
}
