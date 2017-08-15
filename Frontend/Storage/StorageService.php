<?php

namespace EMS\ClientHelperBundle\Frontend\Storage;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class StorageService
{
    /**
     * @var string
     */
    private $basePath;
    
    /**
     * @var Filesystem
     */
    private $filesystem;
    
    /**
     * @param string $basePath
     */
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->filesystem = new Filesystem();
    }
    
    /**
     * @param string $sha1
     * 
     * @return File
     * 
     * @throws FileNotFoundException
     */
    public function getFileBySha1($sha1)
    {
        $path = $this->basePath.'/'.substr($sha1, 0, 3).'/'.$sha1;
        
        return new File($path);
    }
}
