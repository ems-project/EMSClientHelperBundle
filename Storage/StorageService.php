<?php

namespace EMS\ClientHelperBundle\Storage;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
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
    
    /**
     * @return bool
     */
    public function storageExists()
    {
        return file_exists($this->basePath);
    }
    
    /**
     * @return bool
     */
    public function storageIsEmpty()
    {
        $finder = new Finder();
        $iterator = $finder->in($this->basePath)->getIterator();
        $iterator->rewind();
        return !$iterator->valid();
    }
    
    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }


}
