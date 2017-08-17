# EMSBackendBridgeBundle
Contains helper services, controllers and twig extensions for elasticms integration.
## Getting Started
### Config reference
```bash
php bin/console config:dump-reference EMSBackendBridgeBundle
# Default configuration for "EMSBackendBridgeBundle"
ems_backend_bridge:

    # environment name => regex for matching the base url
    request_environment:  []
    elasticms:

        # Prototype: name for the ems-project
        -

            # elasticsearch hosts
            hosts:                [] # Required

            # example: 'test_'
            index_prefix:         ~ # Required

            # example: 'test_i18n'
            translation_type:     null
````
### Request Environment
The config array **request_environment** is required for setting the correct **_environment** attribute, this is done by the RequestListener (EMS\ClientHelperBundle\EMSBackendBridgeBundle\EventListener\RequestListener)

### Services
Foreach elasticms project defined in the bundle configuration the following services are available.

- elasticsearch.client.**projectName**: (Elasticsearch\Client): created by it's factory class (Elasticsearch\ClientFactory). For now we only pass the *hosts* option. More information: https://github.com/elastic/elasticsearch-php
- emsch.client_request.**projectName**: depends on a Elasticsearch\Client and the symfony requestStack. This way the service can make environment based elasticsearch calls.

### Translations
If the config option '**translation_type**' for a elasticms project is defined, the translation loader will automatically be called for all **empty** translation files.
Create empty translation files in (/app/Resources/translations) in the following format: **environment.locale.projectName**. Translations are only created during a cache warmup.

### Twig extensions
- emsch_trans (Twig\TranslationExtension): twig filter that works like a normal trans filter, except it suffix the domain with the current request environment.
    ```
    {{ 'test'|emsch_trans({}, 'projectName') }}
    ```
- emsch_ouuid (Twig\LayoutExtension)
    ```
    {{ 'type:AV02131ywJBP0ULmVzID'|emsch_ouuid }} #returns AV02131ywJBP0ULmVzID
    ```