<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;

class RoutingService
{
    /**
     * @var ClientRequest
     */
    private $clientRequest;
    
    /**
     * @var \Twig_Environment
     */
    private $twig;
    
    /**
     * @param ClientRequest $clientRequest injected by compiler pass
     */
    public function __construct(
        ClientRequest $clientRequest,
        \Twig_Environment $twig
    ) {
        $this->clientRequest = $clientRequest;
        $this->twig = $twig;
    }
    
    /**
     * @param string $type
     * @param string $ouuid
     * 
     * @return false|string
     */
    public function generate($type, $ouuid)
    {
        if (!$document = $this->getDocument($type, $ouuid)) {
            return false;
        }
        
        return $this->twig->render($type, [
            'id'     => $document['_id'],
            'source' => $document['_source'],
            'locale' => $this->clientRequest->getLocale()
        ]);
    }
    
    /**
     * @param string $type
     * @param string $ouuid
     *
     * @return array|false
     */
    private function getDocument($type, $ouuid)
    {
        return $this->clientRequest->getByOuuid($type, $ouuid);
    }
}
