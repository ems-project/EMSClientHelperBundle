<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Helper\Routing\RequestHelper;
use Symfony\Bridge\Twig\Extension\TranslationExtension as BaseExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TwigFilter;

class TranslationExtension extends BaseExtension
{
    /**
     * @var RequestHelper
     */
    private $requestHelper;
    
    /**
     * @param TranslatorInterface  $translator
     * @param RequestHelper        $requestHelper
     * @param NodeVisitorInterface $translationNodeVisitor
     */
    public function __construct(
        TranslatorInterface $translator,
        RequestHelper $requestHelper,
        NodeVisitorInterface $translationNodeVisitor = null
    ) {
        parent::__construct($translator, $translationNodeVisitor);
        
        $this->requestHelper = $requestHelper;
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
        $environment = $this->requestHelper->getEnvironment();
        
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
