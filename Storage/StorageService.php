<?php

namespace EMS\ClientHelperBundle\Storage;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;

class StorageService
{
    /**
     * @var string
     */
    private $path;
    
    /**
     * @var Filesystem
     */
    private $filesystem;
    
    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
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
        return new File($this->getFilename($sha1));
    }
    
    /**
     * @param string $sha1
     *
     * @return string
     */
    private function getFilename($sha1)
    {
        $path = $this->path.'/'.substr($sha1, 0, 3);
        
        if (!$this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path);
        }
        
        return $path . '/' . $sha1;
    }
}
