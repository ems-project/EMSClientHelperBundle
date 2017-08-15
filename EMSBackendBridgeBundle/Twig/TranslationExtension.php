<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Service\RequestService;
use Symfony\Bridge\Twig\Extension\TranslationExtension as BaseExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TwigFilter;

class TranslationExtension extends BaseExtension
{
    /**
     * @var RequestService
     */
    private $requestService;
    
    /**
     * @param TranslatorInterface  $translator
     * @param RequestService       $requestService
     * @param NodeVisitorInterface $translationNodeVisitor
     */
    public function __construct(
        TranslatorInterface $translator, 
        RequestService $requestService,
        NodeVisitorInterface $translationNodeVisitor = null
    ) {
        parent::__construct($translator, $translationNodeVisitor);
        
        $this->requestService = $requestService;
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
