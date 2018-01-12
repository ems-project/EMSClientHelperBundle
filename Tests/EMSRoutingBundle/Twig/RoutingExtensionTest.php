<?php

namespace EMS\ClientHelperBundle\Tests\EMSRoutingBundle\Twig;

use EMS\ClientHelperBundle\EMSRoutingBundle\Service\RoutingService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Twig\RoutingExtension;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;


/**
 * Description of RoutingExtensionTest
 */
class RoutingExtensionTest extends TestCase
{
    /**
     * @var RoutingExtension
     */
    private $extension;
    
    /**
     * @var Mock
     */
    private $routingService;
    
    protected function setUp()
    {
        $this->routingService = Mockery::mock(RoutingService::class);
        $this->extension = new RoutingExtension($this->routingService);
    }
    
    public function testTransformEmsLink()
    {
        $content = 'ems://asset:standards_asset:169ac02da0f0bf4b116cfb248226fb8';
        
        $this->routingService
            ->shouldReceive('generate')
            ->once()
            ->with(\Mockery::on(function (array $match){
                $this->assertEquals('asset', $match['link_type']);
                $this->assertEquals('standards_asset', $match['content_type']);
                $this->assertEquals('169ac02da0f0bf4b116cfb248226fb8', $match['ouuid']);
                
                return true;
            }))
            ->andReturn('success');
        
        $this->assertEquals('success', $this->extension->transform($content));
    }
    
    /**
     * Content_type is optional and can be null
     */
    public function testTransformOptionalContentType()
    {
        $content = 'ems://object:30420d1a9afb2bcb60335812569af4435a59ce17';
        
        $this->routingService
            ->shouldReceive('generate')
            ->once()
            ->with(\Mockery::on(function (array $match){
                $this->assertSame('object', $match['link_type']);
                $this->assertSame('30420d1a9afb2bcb60335812569af4435a59ce17', $match['ouuid']);
                $this->assertArrayNotHasKey('content_type', $match);
                $this->assertArrayNotHasKey('query', $match);
                
                return true;
            }))
            ->andReturn('success');
        
        $this->assertEquals('success', $this->extension->transform($content));
    }
    
    /**
     * The emsLink can contain a query string
     */
    public function testTransformWithQuery()
    {
        $content = '<a href="ems://asset:30420d1a9afb2bcb60335812569af4435a59ce17?name=Desert.jpg&amp;type=image/jpeg " >test</a>';
    
        $this->routingService
            ->shouldReceive('generate')
            ->once()
            ->with(\Mockery::on(function (array $match){
                $this->assertSame('asset', $match['link_type']);
                $this->assertSame('30420d1a9afb2bcb60335812569af4435a59ce17', $match['ouuid']);
                $this->assertArrayNotHasKey('content_type', $match);
                $this->assertSame('name=Desert.jpg&amp;type=image/jpeg', $match['query']);
                
                return true;
            }))
            ->andReturn('success');
        
        $this->assertEquals('<a href="success" >test</a>', $this->extension->transform($content));
    }
}
