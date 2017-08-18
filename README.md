# EMSClientHelperBundle

Find specifications for each sub-bundle in the deticated README files. 

The goal of this collection of bundles is to provide reusable functionalities,
closely related to ElasticMS. 

## EMSBackendBridgeBundle
Functionalities to interact with the elasticsearch cluster containing all data.
It is mostly responsible to fetch content/translations automatically based on the elasticms defined environments.

## EMSLanguageSelectionBundle
Add a "language selection" page and a fallback to add language to url's without language prefix defined.

## EMSRedirectBundle
Add url alias system that is able to override existing controller patterns for any page. 
E.g. If you have a pattern /article/title and /page/title for two content types. You could put a page on /article/custom-url or any other url alias!

## EMSTwigListBundle
When twig templates are statically prepared for the css integrators, 
this bundles allows them to visualise those templates (before any development has occured)