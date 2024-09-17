# Remora Helper module

This module is to help us with styling of `Bootstrap 5 Components` and to use `components templates` in future as a helper for UI.

The module does work on it's own, but needs installed `drupal/bootstrap5` or `mrm-remora/remora_base_theme` for everything to work.

## Installation

1. Add the repository to composer
```json
{
  "name": "mrm-remora/helper_module",
  "type": "vcs",
  "url": "git@github.com:MRM-Remora/helper_module.git"
}
```
2. Run `composer require mrm-remora/helper_module "^1.0"`
3. Enable the module by running `drush pm:e helper_module`
