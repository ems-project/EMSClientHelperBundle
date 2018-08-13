<?php

namespace EMS\ClientHelperBundle\Twig;

use EMS\ClientHelperBundle\Helper\Elasticsearch\ClientRequest;
use Twig\Extension\AbstractExtension;
use Symfony\Component\HttpFoundation\RequestStack;

class EditionExtension extends AbstractExtension
{
    
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(
        RequestStack $requestStack
    ) {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('emsch_admin_menu', 
                [$this, 'showAdminMenu'],
                ['is_safe' => ['html']]
            )
        ];
    }
    
    /**
     * @param  string  $emsLink
     * @return string
     */
    public function showAdminMenu($emsLink)
    {
        $request = $this->requestStack->getCurrentRequest();

        if(null !== $request->get('_backend')){
            $ouuid = ClientRequest::getOuuid($emsLink);
            $type = ClientRequest::getType($emsLink);
            $backend = $request->get('_backend'); 
            
            return 'data-ems-type="' . $type . '" data-ems-key="' . $ouuid . '" data-ems-url="' . $backend . '"';
        }

        return '';
        
    }
    
}
