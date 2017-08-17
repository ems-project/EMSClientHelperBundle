# EMSLanguageSelectionBundle
Contains helper services, controllers and twig extensions for elasticms integration.
## Getting Started
### Config reference
```bash
php bin/console config:dump-reference EMSLanguageSelectionBundle
# Default configuration for "EMSLanguageSelectionBundle"
ems_language_selection:
    supported_locale:

        # Prototype
        -
            locale:               ~
            logo_path:            ~

    # elasticsearch document type for the language options
    option_type:          ~

    # translation domain for the emsch twig translation filter
    emsch_trans_domain:   ~

    # elasticms client defined in EMSBackendBridgeBundle
    ems_client:           ~

    # elasticsearch hosts
    ems_hosts:            [] # Required

    # example: 'test_'
    ems_index_prefix:     ~ # Required
````
### Routing
Add the controller to your routing.yml file
```yml
ems_language_selection:
    resource: '@EMSLanguageSelectionBundle/Resources/config/routing.yml'
````