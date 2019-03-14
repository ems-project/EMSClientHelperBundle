<?php

namespace EMS\ClientHelperBundle\Helper\Elasticsearch;

class HierarchicalStructure
{
    /**@var \array* */
    private $children;
    /**@var \string* */
    private $type;
    /**@var \string* */
    private $id;
    /**@var \string* */
    private $source;
    /** @var mixed  */
    private $data;
    /** @var bool  */
    private $active = false;

    public function __construct(string $type, string $id, array $source, array $activeChild)
    {
        $this->type = $type;
        $this->id = $id;
        $this->source = $source;
        $this->children = [];
        if (!empty($activeChild)) {
            $this->active = ($id === $activeChild['id'] && $type === $activeChild['type']) ? true : false;
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): array
    {
        return $this->source;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): HierarchicalStructure
    {
        $this->data = $data;

        return $this;
    }
    
    public function getActive(): bool
    {
        return $this->active;
    }
    
    public function setActive(bool $active): HierarchicalStructure
    {
        $this->active = $active;

        return $this;
    }

    public function getKey(): string
    {
        return $this->type . ":" . $this->id;
    }

    public function addChild(HierarchicalStructure $child)
    {
        $this->children[] = $child;
        if ($child->getActive()){
            $this->setActive(true);
        }
    }
}