# Routing

## Pdf generation

For enabling pdf generation use the **emsch.controller.pdf** controller
```json
{
    "path": "/{_locale}/example-pdf",
    "controller": "emsch.controller.pdf",
    "requirements": {
      "_locale": "fr|nl"
    }
}
```
In Twig you can set/override the pdf options with custom meta tags in the head section
```html
<head>
    <meta name="pdf:filename" content="example.pdf" />
    <meta name="pdf:attachment" content="true" />
    <meta name="pdf:compress" content="true" />
    <meta name="pdf:html5Parsing" content="true" />
    <meta name="pdf:orientation" content="portrait" />
    <meta name="pdf:size" content="a4" />
</head>
```

## Spreadsheet generation

For enabling spreadsheet generation use the **emsch.controller.spreadsheet** controller
```yaml
test_xlsx:
  config:
    path: /test.xlsx
    controller: 'emsch.controller.spreadsheet'
  template_static: template/test/xlsx.json.twig
  order: 4
```

In Twig you can set the spreadsheet options by generating a JSON
```twig
{% set config = {
    "filename": "custom-filename",
    "disposition": "attachment",
    "writer": "xlsx",
    "sheets": [
        {
            "name": "Sheet 1",
            "rows": [
                ["A1", "A2"],
                ["B1", "B2"],
            ]
        },
        {
            "name": "Sheet 2",
            "rows": [
                ["A1", "A2"],
                ["B1", "B2"],
            ]
        },
    ]
} %}

{{- config|json_encode|raw -}}
```

Two writer are supported:
 - `xlsx`: Generate a Microsoft Excel file
 - `csv`: Generate a CSV file


## Route to an asset

A route may also directly returns an asset:
```json
{
    "path": "/{_locale}/example-pdf/{filename}",
    "controller": "emsch.controller.router:asset",
    "requirements": {
      "_locale": "fr|nl"
    }
}
```

The template must returns a json like this one:

```json
{
  "hash": "aaaaabbbbbcccccdddd111112222",
  "config": {
    "_mime_type": "application/pdf",
    "_disposition": "inline"
  },
  "filename": "demo.pdf"

}
```

 - `hash`: Asset's hash
 - `config`: Config's hash or config array (see common's processor config)
 - `filename`: File name

This json may also contain an optional `immutable` boolean option [default value = false]:

```json
{
  "hash": "aaaaabbbbbcccccdddd111112222",
  "config": {
    "_mime_type": "application/pdf",
    "_disposition": "inline"
  },
  "filename": "demo.pdf",
  "immutable": true
}
```