# Twig functions

## emsch_assets

For managing environment based assets.

This function will unzip the file (hash) in /public/{saveDir}/**hash** (if not exists). 
The default value of the saveDir is **bundles**.

```twig
{{- emsch_assets('hash', 'saveDir') -}}
```

After it will create a symlink /public/{saveDir}/**environment_alias** to the hash directory.
Now you have the possibility to add the following rule in your apache vhost.

```
 Alias /bundles/emsch_assets /opt/src/public/bundles/**$ENVIRONMENT_ALIAS**
```

Example base template.
```twig
<link rel="stylesheet" href="{{ asset('bundles/emsch_assets/css/app.css') }}">
```

## emsch_unzip

Like emsch_assets this will unzip a file into the required saveDir.
The function will also return an array, on success this array will contain the file path as key 
and a Symfony\Component\Finder\SplFileInfo object as value. 

```twig
{% set images = emsch_unzip('cf3adfdc15eae63f2040cf2c737ccb37a06ee1f5', 'example-images') %}
{% for path, info in images %}
    <img src="{{ path }}" alt="{{ info.filename }}" />
{% endfor %}
```

# Twig embed

## render hierarchy

```twig
{{ render(controller('emsch.controller.embed::renderHierarchyAction', {
    'template': '@EMSCH/template/menu.html.twig',
    'parent': 'emsLink',
    'field': 'children',
    'depth': 5,
    'sourceFields': [],
    'args': {'emsLink': emslink, 'extra': 'test'}
} )) }}
```
Example menu.html.twig
```twig
<ul>   
    {% for a, childA in hierarchy.children %}
        <li {% if childA.active %}class="active"{% endif %}>  
            {{ childA.source._contenttype ~ ':' ~ childA.id }}
            {% if childA.children|length > 0 %}      
                <ul>
                    {% for b, childB in childA.children %}
                        <li {% if childB.active %}class="active"{% endif %}>{{ childB.source._contenttype ~ ':' ~ childB.id }}</li>
                    {% endfor %}
                </ul>
            {% endif %}
        </li>
    {% endfor %}
</ul>
```
Example menu.html.twig