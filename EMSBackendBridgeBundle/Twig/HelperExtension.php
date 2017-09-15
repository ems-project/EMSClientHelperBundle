<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
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
            new TwigFilter('dump', array($this, 'dump')),
            new TwigFilter('format_bytes', array($this, 'formatBytes')),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getOuuid($emsLink)
    {
        return ClientRequest::getOuuid($emsLink);
    }
    
    public function dump($object) {
        if(function_exists('dump')){
            dump($object);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emsch_helper';
    }    
    
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
