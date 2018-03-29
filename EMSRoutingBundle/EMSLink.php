<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle;

/**
 * EmsLink
 */
class EMSLink
{
    /**
     * Object, asset, ...
     * 
     * @var string
     */
    private $linkType;
    
    /**
     * @var string|null
     */
    private $contentType = null;

    /**
     * @var string
     */
    private $ouuid;
    
    /**
     * @var string
     */
    private $query = null;
    
    /**
     * @param array $match
     */
    public function __construct(array $match)
    {
        $this->linkType = $match['link_type'];
        $this->ouuid = $match['ouuid'];
        
        if (isset($match['content_type'])) {
            $this->contentType = $match['content_type'];
        }
        
        if (isset($match['query'])) {
            $this->query = html_entity_decode($match['query']);
        }
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return vsprintf('ems:%s:%s%s%s', [
            $this->linkType,
            ($this->contentType ? $this->contentType . ':' : ''),
            $this->ouuid,
            ($this->query ? '?'. $this->query : '')
        ]);
    }
    
    /**
     * @return string
     */
    public function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * @return string|null
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getOuuid()
    {
        return $this->ouuid;
    }
    
    public function getQuery()
    {
        parse_str($this->query, $output);
        return $output;
    }

    /**
     * @return string
     */
    public function hasContentType()
    {
        return null !== $this->contentType;
    }
    
    /**
     * @return bool
     */
    public function isAsset()
    {
        return 'asset' === $this->getLinkType();
    }
}
