# EMSBackendBridgeBundle
Contains helper services, controllers and twig extensions for elasticms integration.
## Getting Started
### Config reference
```bash
php bin/console config:dump-reference EMSTwigListBundle
# Default configuration for "EMSTwigListBundle"
ems_twig_list:
    templates:

        # Prototype
        -
            resource:             ~
            base_path:            ~
    app_enabled:          false
    app_base_path:        []
````
You can find an example configuration in 
`EMSTwigListBundle/Resources/config/config.yml`
### Routing
Add the controller to your routing.yml file
```yml
ems_twig_list:
    resource: '@EMSTwigListBundle/Controller/'
    type : annotation
````