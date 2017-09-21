<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Controller;

use EMS\ClientHelperBundle\EMSRoutingBundle\EMSFileInfo;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\FileManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileController extends AbstractController
{
    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @param FileManager $fileManager
     */
    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }
    
    /**
     * @Route("/file/view/{ouuid}", name="ems_routing_file_view")
     */
    public function viewAction(Request $request, $ouuid)
    {
        //http://blog.alterphp.com/2012/08/how-to-deal-with-asynchronous-request.html
        $request->getSession()->save();
        $info = $this->fileManager->getFileInfo($ouuid);
        
        $response = new Response();
        $response->setPublic();
        $response->setEtag($info->getSha1());
        
        if ($response->isNotModified($request)) {
            return $response; //cached
        }
        
        return $this->FileResponse($info);
    }
    
    /**
     * @param EMSFileInfo $info
     *
     * @return BinaryFileResponse
     */
    private function FileResponse(EMSFileInfo $info)
    {
        $file = $this->fileManager->getFile($info->getSha1());
        
        $response = new BinaryFileResponse($file);
        $response->setEtag($info->getSha1());
        $response->setPublic();
        
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $info->getFilename()
        );
        
        $response->headers->set('Content-Type', $info->getMimeType());
        $response->headers->set('Content-Disposition', $disposition);
        
        return $response;
    }
}
