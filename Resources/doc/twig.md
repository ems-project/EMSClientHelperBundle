# Twig filters

## emsch_anti_spam

For obfuscation of pii on your website when the user agent is a robot.

Implementation details are based on http://www.wbwip.com/wbw/emailencoder.html using `emsch_html_encode`. 
The following data can be obfuscated (even inside a wysiwyg field):

- emailadress `no_reply@example.com`
````twig
{{- 'no_reply@example.com'|emsch_html_encode -}}
````
- phone number in `<a href="tel:____">`
````twig
{{- '<a href="tel:02/787.50.00">repeated here, the number will not be encoded</a>'|emsch_html_encode -}}
````
- custom selection of pii using a span with class "pii"
````twig
{{- '<span class="pii">02/787.50.00</span>'|emsch_html_encode -}}
````

See unit test for more examples.

Note: Phone numbers are only obfuscated if they are found inside "tel:" notation. When a phone is used
outside an anchor, the custom selection of pii method should be used.

Note: When using custom selection of pii, make sure that no HTML tags are present inside the pii span.

Note: the custom selection pii span is only present in the backend. The obfuscation method removes the span
tag from the code that is send to the browser.

## emsch_html_encode

You can transform any text to its equivalent in html character encoding.

````twig
{{- 'text and téxt'|emsch_html_encode -}}
````

See unit test for more examples.

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
    'args': {'extra': 'test'}
} )) }}
```
Example menu.html.twig
```twig
<ul>   
    {% for a, childA in hierarchy.children %}
        <li>  
            {{ childA.source._contenttype ~ ':' ~ childA.id }}
            {% if childA.children|length > 0 %}      
                <ul>
                    {% for b, childB in childA.children %}
                        <li>{{ childB.source._contenttype ~ ':' ~ childB.id }}</li>
                    {% endfor %}
                </ul>
            {% endif %}
        </li>
    {% endfor %}
</ul>
```
Example menu.html.twig