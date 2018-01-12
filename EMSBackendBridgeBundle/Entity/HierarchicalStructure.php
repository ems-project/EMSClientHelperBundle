<?php


namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity;

class HierarchicalStructure {
    /**@var \array**/
    private $children;
    /**@var \string**/
    private $type;
    /**@var \string**/
    private $id;
    /**@var \string**/
    private $source;
    private $data;
    
    /**
     * 
     * @param string $type
     * @param string $id
     * @param array $source
     */
    public function __construct($type, $id, array $source) {
        $this->children = [];
        $this->type = $type;
        $this->id = $id;
        $this->source = $source;
        $this->data = null;
    }
    
    /**
     *
     * @return string*
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     *
     * @return string*
     */
    public function getType() {
        return $this->type;
    }
    
    /**
     * 
     * @return \string*
     */
    public function getSource() {
        return $this->source;
    }
    
    /**
     *
     * @return \array*
     */
    public function getChildren() {
        return $this->children;
    }
    
    /**
     *
     * @return \array*
     */
    public function setChildren($children) {
        $this->children = $children;
    }
    
    /**
     *
     * @return \array*
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * 
     * @param unknown $data
     * @return \EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity\HierarchicalStructure
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }    
    
    /**
     *
     * @return \array*
     */
    public function getKey() {
        return $this->type.":".$this->id;
    }
    
    /**
     *
     * @param HierarchicalStructure $child
     */
    public function addChild(HierarchicalStructure $child) {
        $this->children[]   = $child;
    }
}