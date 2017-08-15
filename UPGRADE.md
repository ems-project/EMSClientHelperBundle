# EMSClientHelperBundle
Whenever breaking changes are present in an upgrade path, we document them here.

## Upgrade from 0.2.3 to 0.3.0

### AppKernel.php
Replace the following line 
```bash
new EMS\ClientHelperBundle\EMSClientHelperBundle()
````
with the following separated bundles (feel free to omit unused parts of the bundle)
```bash
new EMS\ClientHelperBundle\EMSTwigListBundle\EMSTwigListBundle(),
new EMS\ClientHelperBundle\EMSFrontendBundle\EMSFrontendBundle()
````
### config.yml
Each subbundle now has it's own configuration tree. The original config:
(limited to the first hierarchical levels)
```bash
php bin/console config:dump-reference EMSClientHelperBundle
# Default configuration for "EMSClientHelperBundle"
ems_client_helper:
    request_environment:
    elasticms:
    twig_list:
        templates:
        app_enabled:
        app_base_path:
````
Is now splitted per subbundle (find the full reference in README.md)
```bash
php bin/console config:dump-reference EMSTwigListBundle
ems_twig_list:
    templates:
    app_enabled:
    app_base_path:

php bin/console config:dump-reference EMSFrontendBundle
ems_frontend:
    request_environment:
    elasticms:
````

### routing.yml
If you decide to keep using the EMSTwigListBundle, you should modify the routing file.
Otherwise you can simply remove the routing configuration:
```bash
_ems_client_helper:
    resource: "@EMSClientHelperBundle/Resources/config/routing.yml"
    prefix: /_helper
````
becomes
```bash
_ems_twig_list:
    resource: "@EMSTwigListBundle/Resources/config/routing.yml"
    prefix: /_helper
````
### Custom Classes
Your project is using the EMSClientHelperBundle classes. Therefore many classNotFound exceptions will now be thrown.
Find here some exemple changes to get you along:
```bash
EMS\ClientHelperBundle\Elasticsearch\ClientRequest 
--> 
EMS\ClientHelperBundle\EMSFrontendBundle\Elasticsearch\ClientRequest
````
The easiest strategy would be to find and replace 
`ClientHelperBundle` with `ClientHelperBundle\EMSFrontendBundle` in your project source.