<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Twig;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunctions;
use Symfony\Component\HttpFoundation\RequestStack;

class EditionExtension extends AbstractExtension
{
    
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param ContainerInterface $container
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
    public function showAdminMenu($emsLink): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if( isset($request->get('_backend')) )
        {
            $ouuid = ClientRequest::getOuuid($emsLink);
            $type = ClientRequest::getType($emsLink);
            $backend = $request->get('_backend'); 
            
            return 'data-ems-type="' . $type . '" data-ems-key="' . $ouuid . '" data-ems-url="' . $backend . '"';
        }

        return '';
        
    }
    
}
