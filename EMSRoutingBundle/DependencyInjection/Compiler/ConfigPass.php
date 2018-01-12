<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection\Compiler;

use EMS\ClientHelperBundle\EMSRoutingBundle\Service\FileManager;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\UrlHelperService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Twig\TemplateLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $templateLoader = $container->getDefinition(TemplateLoader::class);
        $templateLoader->addMethodCall(
            'setConfig',
            [$container->getParameter('ems_routing.template_loader')]
        );
        
        $fileManager = $container->getDefinition(FileManager::class);
        $fileManager->addMethodCall(
            'setConfig',
            [$container->getParameter('ems_routing.file_manager')]
        );
        
        $urlGenerator = $container->getDefinition(UrlHelperService::class);
        $urlGenerator->addMethodCall(
            'setConfig',
            [$container->getParameter('ems_routing.url_helper')]
        );
    }
}
