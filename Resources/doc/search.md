# Search

## Config

- types: _contenttype field in the result
- fields: free text search in these fields
- synonyms: can be used for translating emsLinks
- sizes: define possible search sizes, default is the first one, use request param **'l'**.
- sorts: key is the value of the request param **'s'**

````json
{
  "types": ["page", "block"],
  "fields": ["all_url_%locale%", "url"],
  "synonyms": ["keyword"],
  "sizes": [10,25,50],
  "sorts": {
      "recent": {"field": "search_date", "order": "desc", "unmapped_type": "date", "missing":  "_last"},
      "title": "title_%locale%.keyword"
  },
  "filters": {
     "ctype": {"type": "terms", "field": "search_type", "aggs_size": 10},
     "fdate": {"type": "date_range", "field": "search_dates"}
   }
}  
````

## Filters

- filterName: the named of the request query parameter.
- type: term, terms, date_range
- field: the search field in the elasticsearch document
- agg_size: for adding the field in aggregations
- post_filter: filter after making aggregations (see Post Filtering)
- optional: if not all docs contain this filter, default false
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

## Nested queries

If facets depends on facets, we can create a nested collection for filtering.

### Example document source's
````json
[
    {
        "name": "person1",
        "tags": [
            {"type": "tag1", "values": [1, 2, 3, 4]},
            {"type": "tag2", "values": [5, 7]},
            {"type": "tag3", "values": [5, 7]}
        ]
    },
    {
        "name": "person2",
        "tags": [
            {"type": "tag2", "values": [1, 2]},
            {"type": "tag4", "values": [5, 7]}
        ]
    }
]
````
### Configuration 2 nested filters
````json
{
  "filters": {
    "personTags": {
      "type": "terms",
      "nested_path": "tags",
      "field": "type",
      "aggs_size": 50,
      "sort_field": "_term",
      "sort_order": "desc"
    },
    "personValues": {
      "type": "terms",
      "nested_path": "tags",
      "field": "values",
      "aggs_size": 50,
      "sort_field": "_term",
      "sort_order": "desc"
    }
  }
}
````

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

## Highhight

Get highlighted snippets from one or more fields in your search 
````json
{
  "highlight": {
    "pre_tags": [
      "<em>"
    ],
    "post_tags": [
      "</em>"
    ],
    "fields": {
      "all_%locale%": {
        "fragment_size": 2000,
        "number_of_fragments": 50
      }
    }
  }
}
````
