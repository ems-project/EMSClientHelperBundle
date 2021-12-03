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

## emsch_assets_version

This is similar to [emsch_assets](#emsch_assets) but using the hash as version strategy for the assets. No need to add an alias rule in the Vhost file.

This function will unzip the file (hash) in /public/{saveDir}/**hash** (if not exists).
The default value of the saveDir is **bundles**.
```twig
{{- emsch_assets_version('hash', 'saveDir') -}}
```

This function can be called only one time per Twig rendering. Otherwise, an error will be thrown.

Example base template.
```twig
<link rel="stylesheet" href="{{ asset('css/app.css', 'emsch') }}">
```

When you are developing you may want to use asset in a local folder (in the `public` folder) instead of a zip file. In order to do so, use the `EMSCH_ASSET_LOCAL_FOLDER` environment variable


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

## ems_search_config

For accessing the search configuration (filters) before doing the actual search.
````twig
{% set search = emsch_search_config() %}
{% set choices = search.getFilter('name').getChoices() %}
````

In a search result page the search is passed to the template.
````twig
{% set activeFilters = search.getActiveFilters() %}
{% set choices = search.getFilter('name').getChoices() %}
````

Sorting example
````twig
{% if search.sorts|length > 0 %}
    <div class="custom-control custom-radio">
      <input type="radio" id="sortby_relevance" name="s" value="" class="custom-control-input" {{ null == search.sortBy ? 'checked="checked"' }}>
      <label class="custom-control-label" for="sortby_relevance">{{ 'sortby_relevance'|trans }}</label>
    </div>
    {% for s, sort in search.sorts %}
        <div class="custom-control custom-radio">
          <input type="radio" id="sortby_{{ s }}" name="s" value="{{ s }}" class="custom-control-input" {{ sort.field == search.sortBy ? 'checked="checked"' }} >
          <label class="custom-control-label" for="sortby_{{ s }}">{{ ('sortby_'~s)|trans }}</label>
        </div>
    {% endfor %}
{% endif %}
````

# Twig embed

## render hierarchy

```twig
{{ render(controller('emsch.controller.embed::renderHierarchyAction', {
    'template': '@EMSCH/template/menu.html.twig',
    'parent': 'emsLink',
    'field': 'children',
    'depth': 5,
    'sourceFields': [],
    'args': {'activeChild': emsLink, 'extra': 'test'}
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