# Search

## Config

````json
{
  "types": ["page", "block"],
  "fields": ["all_url_%locale%", "url"],
  "synonyms": ["keyword"],
  "filters": {
     "ctype": {"type": "terms", "field": "search_type", "aggs_size": 10},
     "fdate": {"type": "date_range", "field": "search_dates"}
   }
}  
````

## Filters

- filterName: the named of the request query parameter.
- type: terms of date_range
- field: the search field in the elasticsearch document
- agg_size: for adding the field in aggregations

````json
{
   "filterName": {"type":  "type", "field":  "field", "aggs_size": 10}
}
````

### DateRange 

Example uri for filtering all documents in november 2018.

/search?**fdate[start_date]**=1-11-2018&**fdate[end_date]**=30-11-2018

## Synonyms

Translate emsLinks inside a search result.

### Simple, 

will search and match with the **_all** field.
````json
{
  "synonyms": ["keyword"]
}
````

### Advanced

- field: search result field
- types: will match on _contenttype
- search: search field for synonym
- filter: apply extra filter for searching synonyms
````json
{
  "synonyms": [
      {
        "field": "search_keywords",
        "types": [
          "keyword"
        ],
        "search": "title_%locale%",
        "filter": {
          "exists": {
            "field": "code"
          }
        }
      }
    ]
}
````
