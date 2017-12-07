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
        
        return $this->fileResponseCache($info, $request);
    }
    
    /**
     * @Route("/file/asset/{sha1}", name="ems_routing_file_asset")
     */
    public function assetAction(Request $request, $sha1)
    {
        //http://blog.alterphp.com/2012/08/how-to-deal-with-asynchronous-request.html
        $request->getSession()->save();
        $filename = $request->get('filename', 'filename_unknown');
        $mimetype = $request->get('mimetype', 'mimetype_unknown');
        $info = new EMSFileInfo($filename, $mimetype, $sha1);
        
        return $this->fileResponseCache($info, $request);
    }
    
    private function fileResponseCache(EMSFileInfo $info, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setEtag($info->getSha1());
        
        if ($response->isNotModified($request)) {
            return $response; //cached
        }
        
        return $this->fileResponse($info);
    }
    
    /**
     * @param EMSFileInfo $info
     *
     * @return BinaryFileResponse
     */
    private function fileResponse(EMSFileInfo $info)
    {
        $file = $this->fileManager->getFile($info->getSha1());
        
        $response = new BinaryFileResponse($file);
        $response->setEtag($info->getSha1());
        $response->setPublic();
        
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $this->toAscii($info->getFilename())
        );
        
        $response->headers->set('Content-Type', $info->getMimeType());
        $response->headers->set('Content-Disposition', $disposition);
        
        return $response;
    }
    
    /**
     * Convert a tring into an url friendly string
     * http://cubiq.org/the-perfect-php-clean-url-generator
     *
     * COPIED FROM EMSCoreBundle/Twig/AppExtension.php
     * Modified to accept "." in result string
     * @param string $str
     * @return string
     */
    private function toAscii($str) {
        $clean = $str;
    
        try {
            $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        }
        catch (\Exception $e) {
            $clean = false;
        }
    
        if ( $clean === false ) {
            $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
            $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
            $clean = str_replace($a, $b, $str);
        }
    
        $clean = preg_replace("/[^a-zA-Z0-9\_\|\ \-\.]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/\_\|\ \-]+/", '-', $clean);
    
        return $clean;
    }
}