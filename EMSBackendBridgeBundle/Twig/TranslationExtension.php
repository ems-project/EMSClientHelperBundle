<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\RequestService;
use Symfony\Bridge\Twig\Extension\TranslationExtension as BaseExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TwigFilter;
use Symfony\Component\Asset\Packages;
use Twig\TwigFunction;

class TranslationExtension extends BaseExtension
{
    /**
     * @var RequestService
     */
	private $requestService;
	
	/**
	 *
	 * @var Packages
	 */
	private $packages;
	
	/**
	 *
	 * @var integer
	 */
	private $counter;
    
    /**
     * @param TranslatorInterface  $translator
     * @param RequestService       $requestService
     * @param NodeVisitorInterface $translationNodeVisitor
     */
    public function __construct(
        TranslatorInterface $translator, 
        RequestService $requestService,
    	Packages $packages,
        NodeVisitorInterface $translationNodeVisitor = null
    ) {
        parent::__construct($translator, $translationNodeVisitor);
        
        $this->requestService = $requestService;		
        $this->packages = $packages;
        $this->counter= 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('emsch_trans', array($this, 'trans')),
        ];
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
    	
    	return [
    			new TwigFunction('backend_link', array($this, 'getBackendLink')),
    	];
    }
    
    
    /**
     * {@inheritdoc}
     */
    public function getBackendLink($type, $id)
    {
    	$backendUrl = $this->requestService->getBackendUrl();
    	if(!$backendUrl) {
    		return '';
    	}
    	
    	$backendUrl = preg_replace(['/__type__/', '/__id__/'], [$type, $id], $backendUrl);
    	
    	$emsLogo = $this->packages->getUrl('bundles/emsbackendbridge/images/ball.svg');
    	$out = '<a href="'.$backendUrl.'" target="'.$this->requestService->getEnvironment().'_ems_backend_target" id="ems_backend_link_'.$this->counter.'" style="width:20px;height:20px;display: block; position: absolute;border:0;margin:0;padding:0;"><img src="'.$emsLogo.'" style="height: 20px;border:0;margin:0;padding:0;"><script>(function() {var backendLink = document.getElementById("ems_backend_link_'.($this->counter++).'");backendLink.parentElement.onmouseenter = function(){backendLink.style.display="block";};backendLink.parentElement.onmouseleave=function(){backendLink.style.display="none";};})();</script></a>';
    	++$this->counter;
    	return  $out;
    }
    
    /**
     * {@inheritdoc}
     */
    public function trans($message, array $arguments = array(), $domain = null, $locale = null)
    {
        $environment = $this->requestService->getEnvironment();
        
        return parent::trans($message, $arguments, $domain.'_'.$environment, $locale);
    }

        /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emsch_translator';
    }
}
