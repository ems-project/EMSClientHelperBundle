<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle;

use Symfony\Component\HttpFoundation\File\File;

/**
 * Document elasticsearch
 */
class EMSFileInfo
{
    /**
     * @var string
     */
    private $filename;
    
    /**
     * @var string
     */
    private $mimetype;
    
    /**
     * @var string
     */
    private $sha1;
    
    /**
     * @param string $filename
     * @param string $mimetype
     * @param string $sha1
     */
    public function __construct($filename, $mimetype, $sha1)
    {
        if ("image/svg xml" === $mimetype){
            $mimetype = "image/svg+xml";
        }

        $this->filename = $filename;
        $this->mimetype = $mimetype;
        $this->sha1 = $sha1;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimetype;
    }

    /**
     * @return string
     */
    public function getSha1()
    {
        return $this->sha1;
    }
}