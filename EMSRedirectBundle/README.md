# EMSRedirectBundle
Contains helper services, controllers and twig extensions for elasticms integration.
## Getting Started
Implement a service that implements the interface `EMS\ClientHelperBundle\EMSRedirectBundle\Service\RedirectRouterServiceInterface`.
Then load the factory `EMS\ClientHelperBundle\EMSRedirectBundle\Service\RedirectServiceFactory` with your service.

### Example serivce definition
```yml
AppBundle\Service\RedirectRouterService:
    arguments: 
        - '@router'

EMS\ClientHelperBundle\EMSRedirectBundle\Service\RedirectService:
    factory: [EMS\ClientHelperBundle\EMSRedirectBundle\Service\RedirectServiceFactory, create]
    arguments:
        - '@emsch.client_request.project'
        - '@AppBundle\Service\RedirectRouterService'
        - '%project_redirect_type%'
````