<?php

namespace EMS\ClientHelperBundle\EMSRoutingBundle\DependencyInjection\Compiler;

use EMS\ClientHelperBundle\EMSRoutingBundle\Service\FileManager;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\RedirectService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Service\UrlHelperService;
use EMS\ClientHelperBundle\EMSRoutingBundle\Twig\TemplateLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

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

        $redirectService = $container->getDefinition(RedirectService::class);
        $this->configureRedirectService($container, $redirectService, $container->getParameter('ems_routing.redirection'));
    }

    private function configureRedirectService(ContainerBuilder $container, Definition $redirectService, array $config)
    {
        $clientRequestId = $config['client_request'];
        if (!$container->has($clientRequestId)) {
            throw new InvalidArgumentException(
                sprintf("ems_routing.client_request service '%s' not found!", $clientRequestId)
            );
        }

        $redirectService->addMethodCall(
            'setClientRequest',
            [$container->findDefinition($clientRequestId)]
        );

        $redirectService->addMethodCall(
            'setRedirectType',
            [$config['redirect_type']]
        );
    }
}
