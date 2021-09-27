# Environments

Environments are used to associate HTTP base urls to a specific config. It's usually defined in the EMSCH_ENVS skeleton environments variable. 

## Basic example
 ```json
    {
      "preview": {
        "regex": "/.*/",
        "alias": "webonem_preview"
      }
    }
```

In this example every request are associated to the webonem_preview elasticsearch alias

## Other options 

### remote_cluster

Allow to refer a remote cluster on which elasticsearch queries will be made. If not define queries will be made on the defined cluster itself.

I.e.:

```json

```