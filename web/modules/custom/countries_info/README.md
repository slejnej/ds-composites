# countries_info
Provides Countries and Regions taxonomies and required permissions

Based on https://git.drupalcode.org/project/countries_info

INTRODUCTION
------------
This module provides a taxonomy of the countries (Countries information) which
contains information like ISO2 code, ISO3 code, name, official name, numcode.

REQUIREMENTS
------------
Drupal 8.x

INSTALLATION
------------
1.  Place countries_info module into your modules directory.
    This is normally the "modules" directory.

2.  Go to admin/modules. Enable modules.
    The Countries info module is found in the Taxonomy section.

CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration.

FEATURES
--------

1. This module covers the countries standard name, official name,
   ISO 3166-1 alpha-2 codes, ISO 3166-1 alpha-3 code, UN numeric code
   (ISO 3166-1 numeric-3) and continent (Africa, Antarctica, Asia, Europe,
   NorthAmerica, Oceania, South America).

For example, Taiwan has the following values:

* Name           - Taiwan
* Official name  - Taiwan, Republic of China
* ISO alpha-2    - TW
* ISO alpha-3    - TWN
* ISO numeric-3  - 158
* Continent      - Asia
* Published      - Yes

The official names were originally taken from WikiPedia [2] and the majority
of the continent information was imported from Country codes API project [3].
This have been since standardized with the ISO 3166-1 standard.

2. This is taxonomy based so user can use this taxonomy as a entity reference
   with any content type which can utilize in serach, facet filter etc.

3. User can enable or disable any countries from the 'Countries information'
   taxonomy term list.

SIMILAR MODULES
----------
https://www.drupal.org/project/countries_taxonomy - This module does not
have information like ISO3 code, name, official name, numcode and continent.
