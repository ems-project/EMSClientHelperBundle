# EMSClientHelperBundle
Contains helper services, controllers and twig extensions for elasticms integration.
## Getting Started
### Config reference
```bash
php bin/console config:dump-reference EMSClientHelperBundle
# Default configuration for "EMSClientHelperBundle"
ems_client_helper:
    elasticms:
        # Prototype: name for the ems-project
        -
            # elasticsearch hosts
            hosts:                [] # Required
            # example: ['preview', 'staging', 'live']
            environments:         [] # Required
            # example: 'test_'
            index_prefix:         ~ # Required
            # example: 'test_i18n'
            translation_type:     null
    twig_list:
        templates:
            # Prototype
            -
                resource:             ~
                base_path:            ~
        app_enabled:          false
        app_base_path:        []
````
### Services
Foreach elasticms project defined in the bundle configuration the following services are available.

- elasticsearch.client.**projectName**: (Elasticsearch\Client): created by it's factory class (Elasticsearch\ClientFactory). For now we only pass the *hosts* option. More information: https://github.com/elastic/elasticsearch-php
- emsch.client_request.**projectName**: depends on a Elasticsearch\Client and the symfony requestStack. This way the service can make environment based elasticsearch calls.

## Route loader
The bundle has a custom route loader (EMS\ClientHelperBundle\Routing\EnvironmentLoader). This loader makes it possible for loading the same controllers for each environment. It will prefix the route name with environment name and add the environment as a default value to each controller.

**app/config/routing.yml**
```yaml
preview:
    resource: "preview|@AppBundle/Controller/"
    prefix:   /preview/{_locale}
    type: emsch_environment
staging:
    resource: "staging|@AppBundle/Controller/"
    prefix:   /staging/{_locale}
    type: emsch_environment
```
```bash
php bin/console debug:route
preview_homepage           ANY      ANY      ANY    /preview/{_locale}/home
staging_homepage           ANY      ANY      ANY    /staging/{_locale}/home
```

### Translations
If the config option '**translation_type**' for a elasticms project is defined, the translation loader will automatically be called for all **empty** translation files.
Create empty translation files in (/app/Resources/translations) in the following format: **environment.locale.projectName**. Translations are only created during a cache warmup.

### Twig extensions
- emsch_translator (Twig\TranslationExtension): provide a twig filter '**emsch_trans**' that works like a normal trans filter, except it suffix the domain with the current request environment.
    ```
    {{ 'test'|emsch_trans({}, 'projectName') }}
    ```
- emsch_routing (Twig\RoutingExtension): provides a twig function '**emsch_path**' that works like a normal path function, except it prefix the route name with the current request environment.
    ```
    {{ emsch_path(homepage, {}) }}
    ```