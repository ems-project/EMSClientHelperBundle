<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HelperExtension extends AbstractExtension
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestService       $requestService
     */
    public function __construct( RequestStack $requestStack )
    {
        $this->requestStack = $requestStack;
    }



    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('emsch_ouuid', array($this, 'getOuuid')),
            new TwigFilter('dump', array($this, 'dump')),
            new TwigFilter('format_bytes', array($this, 'formatBytes')),
            new TwigFilter('array_key', array($this, 'arrayKey')),
            new TwigFilter('locale_attr', array($this, 'localeAttribute')),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function localeAttribute($array, $attribute)
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        if(isset($array[$attribute.$locale]))
        {
            return $array[$attribute.$locale];
        }
        return '';

    }

    /**
     * {@inheritdoc}
     */
    public function arrayKey($array, $arrayKey='key')
    {
        $out = [];
        foreach ($array as $id => $item)
        {
            if (isset($item[$arrayKey]))
            {
                $out[$item[$arrayKey]] =  $item;
            }
            else
            {
                $out[$id] =  $item;
            }
        }
        return $out;
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
