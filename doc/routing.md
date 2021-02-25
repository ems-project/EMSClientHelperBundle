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

## Route to an asset
```json
{
    "path": "/{_locale}/example-pdf/{filename}",
    "controller": "emsch.controller.router:asset",
    "requirements": {
      "_locale": "fr|nl"
    }
}
```

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