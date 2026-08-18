# Webform pod
Provides functionality of adding webforms with changed functionalities from patch in the module

## Pod field/region availability
- Main
- Postscript
- Sidebar

## Installation

Add to the aws ApplicationStart.sh hook after updatedb command line 

./vendor/drush/drush/drush webform:repair --no-interaction

Add the snippet under extra in the project composer.json

"merge-plugin": {
      "include": [
        "web/modules/contrib/webform/composer.libraries.json"
      ]
    }

When module installed, for local libraries and no issues with security module, run 

composer update drupal/webform "drupal/webform-*" --with-dependencies
