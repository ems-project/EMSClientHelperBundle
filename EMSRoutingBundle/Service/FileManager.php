<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\Service;

use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Elasticsearch\ClientRequest;
use EMS\ClientHelperBundle\EMSBackendBridgeBundle\Storage\StorageService;
use EMS\ClientHelperBundle\EMSRoutingBundle\EMSFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class FileManager
{
    /**
     * @var PropertyAccessor
     */
    private $accessor;
    
    /**
     * @var array
     */
    private $config;
    
    /**
     * @var ClientRequest
     */
    private $clientRequest;

    /**
     * @var StorageService
     */
    private $storageService;
    
    /**
     * @param ClientRequest  $clientRequest injected by compiler pass
     * @param StorageService $storageService
     */
    public function __construct(
        ClientRequest $clientRequest,
        StorageService $storageService
    ) {
        $this->clientRequest = $clientRequest;
        $this->storageService = $storageService;
        
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }
    
    /**
     * @param string $ouuid
     * 
     * @return EMSFileInfo
     */
    public function getFileInfo($ouuid)
    {
        $document = $this->getDocument($ouuid);
        
        return new EMSFileInfo(
            $this->getDocumentValue($document, 'filename'),
            $this->getDocumentValue($document, 'mimetype'),
            $this->getDocumentValue($document, 'sha1')
        );
    }
    
    /**
     * @param string $sha1
     *
     * @return File
     */
    public function getFile($sha1)
    {
        return $this->storageService->getFileBySha1($sha1);
    }
        
    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * @param string $ouuid
     *
     * @return array
     * 
     * @throws \Exception
     */
    private function getDocument($ouuid)
    {
        $document = $this->clientRequest->getByOuuid(
            $this->config['content_type'], 
            $ouuid,
            $this->getDocumentSource()  
        );
        
        if (!$document) {
            throw new \Exception('document not found!');
        }
        
        return $document;
    }
    
    /**
     * Convert property path to elasticsearch source
     * 
     * [test][test] => test.test
     */
    private function getDocumentSource()
    {
        $source = [];
        
        foreach ($this->config['property_paths'] as $propertyPath) {
            $source[] = \str_replace(['][', '[', ']'], ['.', '', ''], $propertyPath);
        }
        
        return $source;
    }
    
    /**
     * @param array  $document
     * @param string $property filename|mimetype|sha1
     *
     * @return mixed
     */
    private function getDocumentValue(array $document, $property)
    {
        $propertyPath = $this->config['property_paths'][$property];
        
        return $this->accessor->getValue($document['_source'], $propertyPath);
    }
}
