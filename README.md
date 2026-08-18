# ds-composites

This is Remora project created from boilerplate.

## Setup (when initial was done)

1. Run `lando start`
2. Run `cp config/settings.local.php web/sites/default`
3. Run `cp config/development.services.yml web/sites/default`
4. Login details can be found in LastPass
5. Run `lando ant assets`

### Drush
Drush is configured for multisite. Each site has its own Drush alias.
To use Drush for a specific site, run `lando drush @dsc` or `lando drush @dsm`.

TODO:
- na compositih banner in link na machining stran
- kar je na kompozitih mora bit na machining
- na kompozitih bo trgovina in osnovna obrazlaga za machining
- na kompozitih v meniju link na machining in obratno
- 