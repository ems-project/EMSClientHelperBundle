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
- post_filter: filter after making aggregations (see Post Filtering)
- optional: if not all docs contain this filter

````json
{
   "filterName": {"type":  "type", "field":  "field", "aggs_size": 10, "post_filter":  true, "optional":  true}
}
````

### Private filter

By setting the option **public** to false the filter will not get his value from the request query.
You pass the private value with the **value** option.

````json
{
   "filterName": {"type":  "terms", "field":  "_contenttype", "public":  false, "value":  ["page"]}
}
````

### Post Filtering

By default post filtering is enable for public **terms** filters. This way the aggregations are computed before filtering,
we still known the counts of other choices.

[elasticsearch doc](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-post-filter.html)

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
