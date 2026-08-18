# Remora search

This module contains all the settings for Searh API and SOLR. A default search index.
A defaults search view.

## Dependencies

### Drupal modules

| Module name             | Version | Extended README                                        | Comments                                                                                                                      |
|-------------------------|---------|--------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| drupal/search_api       | ^1.31   | [open](https://www.drupal.org/project/search_api)      | This module provides a framework for easily creating searches on any entity known to Drupal, using any kind of search engine. |
| drupal/search_api_solr  | ^4.3    | [open](https://www.drupal.org/project/search_api_solr) | This module provides a Apache Solr backend for the Search API module.                                                         |

## Installation

1. Add the repository to composer

```json
    {
        "name": "mrm-remora/drupal_search",
        "type": "vcs",
        "url": "git@github.com:MRM-Remora/drupal_search.git"
    } 
```
2. Run `composer require mrm-remora/drupal_search "^1.0"`
3. Enable the module by running `drush pm:e drupal_search`

## Patches
| Module name             | Patch name             | Purpose                                                                                                                                                                                                  |
|-------------------------|------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| drupal/facets           | ajax-blocks-pt-2.patch | One of three patches needed for AJAX blocks to work. This is a slightly tweaked version of [this](https://www.drupal.org/files/issues/2023-04-26/ajax_facet_block_views_context-2986981-35.patch) patch. |

## Usage 

### Services
| Name                           | Description                                                                                                                                                             |
|--------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| drupal_search.event_subscriber | EventSubscriber so we are able to override the search rendered ouput settings for the default search index for all content types                                        |
| drupal_search.cache_service    | Caches list of machine names of content types which is used for dynamic change of display views in field rendered_item in search index and display views in search view |

# Hook implementations

| Name                        | Runs when?                   | Purpose                                                                                                              |
|-----------------------------|------------------------------|----------------------------------------------------------------------------------------------------------------------|
| hook_page_attachments_alter | Runs before any page         | Attaches search.js where is the logic for automatic sorting by Relevance if text filter is used                      |
| hook_views_pre_render       | Runs before view is rendered | Automatically changes display views options of all CTs to the search_index display view before rendering search view |

# Patches
There are a couple patches in there for drupal/facets. They are needed to get drupal facets working with AJAX blocks. God knows what exactly they do, but they add GET parameters which are needed for them to work properly. Without these patches, BEF breaks the facets.
