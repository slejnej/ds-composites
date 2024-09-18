# Remora core

This module contains all the core functionalities for Remora to get the different components to work together properly.
While this README contains information on how certain components work (together) and are meant to be implemented, a more comprehensive document can be
found [here](https://www.notion.so/mantaray/Technical-documentation-86206667613c46978f0dd8000fc44cd9).

## Dependencies

### MRM packages

| MRM package                   | Version | Extended README                                                                |
|-------------------------------|---------|--------------------------------------------------------------------------------|
| mantaraymedia/cache_service   | v1.0.2  | [open](https://github.com/MantaRayMedia/cache_service/blob/master/README.md)   |
| mantaraymedia/base_repository | v1.3.0  | [open](https://github.com/MantaRayMedia/base_repository/blob/master/README.md) |

### Drupal modules

| Module name                     | Version | Extended README                                                           | Comments                                                                                                             |
|---------------------------------|---------|---------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------|
| drupal/admin_toolbar            | ^1.3.2  | [open](https://www.drupal.org/project/admin_toolbar)                      |                                                                                                                      |
| drupal/administerusersbyrole    | ^3.4    | [open](https://www.drupal.org/project/role_delegation)                    | Allows us to limit the roles a user can access. E.g. webadmins can't edit other webadmins but they can edit editors. |
| drupal/responsive_image         | 10.1.6  | [open](https://www.drupal.org/docs/mobile-drupal-sites/responsive-images) | Allows creation of Responsive image styles.                                                                          |

### Installing module dependencies
If a new module is added to the list of dependencies in the .info.yml file you will need to add an update_hook to the .module file to install module dependencies.<br>
There is a service `InstallDependenciesService`. Example call below
```
function MODULE_NAME_update_9005() {
  $updater = Drupal::service('remora_core.service.install_dependencies');
  $updater->installModuleDependencies('MODULE_NAME');
}
```

### Updated permissions in custom modules

If remora custom module in new release has updated permissions, it needs to implement update hook where it is going to call permission manager to resolve permissions.
Initially, this is done on module installation by the remora_core, but it only happens on install so for every further update, we need update hook.
There is a service `ModulePermissionsManager`. Example call below
```
function MODULE_NAME_update_9005() {
  try {
    /** @var ModulePermissionsManager $permissionsManager */
    $permissionsManager = Drupal::service('remora_core.manager.module_permissions');
    $permissionsManager->handleModulePermissions('MODULE_NAME');
  } catch(Exception $e) {
    Drupal::service('remora_core.logger')->error($e->getMessage());
  }
}
```

### Updated config ignore in custom modules

If remora custom module in new release has updated config ignore, it needs to implement update hook where it is going to call config ignore manager to resolve config ignore.
Initially, this is done on module installation by the remora_core, but it only happens on install so for every further update, we need update hook.
There is a service `ConfigIgnoreManager`. Example call below
```
function MODULE_NAME_update_9005() {
  try {
    /** @var ConfigIgnoreManager $configIgnoreManager */
    $permissionsManager = Drupal::service('remora_core.manager.config_ignore');
    $permissionsManager->addToConfigIgnore('MODULE_NAME');
  } catch(Exception $e) {
    Drupal::service('remora_core.logger')->error($e->getMessage());
  }
}
```

## Installation

1. Add the repository to composer

```json
    {
        "name": "mrm-remora/remora_core",
        "type": "vcs",
        "url": "git@github.com:MRM-Remora/remora_core.git"
    },
    {
        "name": "mantaraymedia/cache_service",
        "type": "vcs",
        "url": "git@github.com:MantaRayMedia/cache_service.git"
    },
    {
        "name": "mantaraymedia/base_repository",
        "type": "vcs",
        "url": "git@github.com:MantaRayMedia/base_repository.git"
    }
```
2. Add `"extra.enable-patching": true` to composer.json
3. Run `composer require mrm-remora/remora_core "^1.0"`
4. Enable the module by running `drush pm:e remora_core`

### Settings
The module offers two sentry keys to be set, DSN and env. Add the below to your settings.local.php
```php
$settings['remora_core']['sentry'] = [
  'dsn' => '<SENTRY_URL>',
  'env' => '<ENVIRONMENT>'
];
```

## Usage

### Tokens
| Name                     | Description                                                                                             |
|--------------------------|---------------------------------------------------------------------------------------------------------|
| [remora:seo_title]       | Returns the alternative title of the current page, or the node title if alternative title is undefined. |
| [remora:seo_description] | Returns the summary of the current page.                                                                |

### Hooks
| Name                      | Runs when?                                       | Short description                                                                                            |
|---------------------------|--------------------------------------------------|--------------------------------------------------------------------------------------------------------------|
| hook_modules_installed    | Runs when one or multiple modules are installed. | Scans the installed modules for `config/remora/permissions.yml` and adds the permissions to the roles.       |
| hook_preprocess_file_link | File link template rendered.                     | Gets the name value from the media entity and allows template to display it.                                 |
| hook_page_attachments     | Page is loaded.                                  | Adds our custom tonic theme library to the page if page is an administration page so we can override themes. |
| hook_preprocess_paragraph | Paragraph is loaded.                             | Adds human readable name so we can use it in the preview template.                                           |
| hook_node_save            | A node is saved                                  | Updates the metatags for the node to reflect the most up-to-date SEO fields.                                 |

### Cache

| Operation | CID                                | Data contained                                               | Description                                                                      |
|-----------|------------------------------------|--------------------------------------------------------------|----------------------------------------------------------------------------------|
| CREATE    | remora_referenceable_content_types | A list of all Content Types that can be references on fields | Improving performance so Event isn't resolved every time                         |
| CREATE    | remora_core.settings               | A list of all `$settings['remora_core']` values              | Improving performance so settings isn't read multiple times per request.         |
| CREATE    | remora_core.release                | The current release version, taken from the DRUPAL_ROOT      | Improving performance so pregmatched isn't performed multiple times per request. |

### Services
| Name                                   | Description                                                                  |
|----------------------------------------|------------------------------------------------------------------------------|
| remora_core.logger                     | Logger interface with a channel specific to `remora_core`                    |
| remora_core.repository.node            | The BaseRepository's NodeRepository as a service.                            |
| remora_core.repository.taxonomy_term   | The BaseRepository's TermRepository as a service.                            |
| remora_core.repository.user            | The BaseRepository's UserRepository as a service.                            |
| remora_core.manager.module_permissions | Handles all Remora modules' permission files.                                |
| remora_core.service.module_template    | Generates a theme array for each twig file in a module's template directory. |
| remora_core.logger.raven               | Decorates Raven's logger to supply it with the $_SERVER superglobals.        |

### Registering submodule templates
To register a submodule's templates, you need to add the following to your module's _theme hook.

```php
/**
 * Automatically registers all the templates in the templates/ directory
 * @return array The theme containing the theme implementations (templates)
 */
function my_module_theme(): array
{
  return Drupal::service('remora_core.service.module_template')->generateThemesFromTemplates('my_module');
}
```

### Routing
Routes can be generated in JS by calling `Drupal.Routing.generate('route_name', {param: 'value'})`.
For a route to be available in JS, it must be have the `_remora_core.expose` option set to true.
All routes will have their parameters replaced with the values provided in the JS call. Missing parameters will be replaced with the route's default value if available. If not, an exception will be thrown.
Routing file is re-generated on cache rebuild.

Note that URLs won't be URL encoded, so you should ensure that the values you pass are safe to use in a URL.

**Example route**
```yml
remora_core.settings.palettes:
  path: '/admin/config/remora/palettes/{param}/{another}'
  defaults:
    _form: '\Drupal\remora_core\Form\PalettesForm'
    _title: 'Palette Settings'
    param: 'hi mum'
  requirements:
    _permission: 'administer site configuration'
  options:
    _remora_core:
      expose: true
```

**Example calls**
```js
Drupal.routing.generate('remora_core.settings.palettes', {param: 'hello', another: 'yes'}) // result: `/admin/config/remora/palettes/hello/yes`
Drupal.routing.generate('remora_core.settings.palettes', {another: 'yes son'}) // result: `/admin/config/remora/palettes/hi mum/yes son`
Drupal.routing.generate('remora_core.settings.palettes', {param: ':('}) // Exception: missing parameter another
Drupal.routing.generate('xxx') // Exception: missing route
```



### Raven / Sentry
Sentry credentials are added using a decorator, which reads the config values from `$settings['remora_core']['sentry']`.
A RavenOptionSubscriber will strip out any breadcrumbs categorized as "user", to prevent potential user data from being leaked.
All JS and PHP exceptions are logged, as well as CSP errors.

**To disable Sentry** simply omit the DSN from the settings.local.php file.


### Forms
| Name                        | Description                                                                                  |
|-----------------------------|----------------------------------------------------------------------------------------------|
| ExtendedSiteInformationForm | A new collapsible section in the Basic site settings to set a site-wide default button label |
| ExtendedDeleteComponentForm | Overwrites the help text when deleting a nugget to be more descriptive.                      |

## Routes
| Route name                       | Path                                      | Description                                                                      |
|----------------------------------|-------------------------------------------|----------------------------------------------------------------------------------|
| remora_core.settings             | /admin/config/remora                      | The settings page for the module                                                 |
| remora_core.permissions.reimport | /admin/config/remora/permissions/reimport | Reimports the permissions from each module's config/remora/permissions.yml file. |


## Twig extensions
| Name                    | Description                                                                               |
|-------------------------|-------------------------------------------------------------------------------------------|
| BootstrapIconExtensions | Used to get full path to bootstrap icons so you can embed sprites in twig template easily |
| ModuleCheckExtension    | Used to check if a module is installed on the site                                        |

### Array
| Name       | Type   | Arguments         | Description                                                            | Usage                                                 |
|------------|--------|-------------------|------------------------------------------------------------------------|-------------------------------------------------------|
| merge_safe | filter | arrays: ...?array | Functionally the same as the `merge` filter, but works with null types | `{% set test = array1\|merge_safe(array2, array3) %}` |

### Fields
**Note**: `has_any` and `any_fields_not_empty` are functionally the same, just called differently.

| Name                 | Type     | Arguments                        | Description                                                                                                       | Usage                                                                   |
|----------------------|----------|----------------------------------|-------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| has_any              | filter   | content: array, fields: string[] | Returns true if the content has any of the fields set, false otherwise.                                           | `{% if content\|has_any('field_test', 'field_test2') %}`                |
| any_fields_not_empty | function | content: array, fields: string[] | Returns true if the content has any of the fields set, false otherwise.                                           | `{% if any_fields_not_empty(content, ['field_test', 'field_test2']) %}` |


### User
| Name               | Type | Arguments       | Description                                                                                                       | Usage                                                            |
|--------------------|------|-----------------|-------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------|
| assigned_role      | test | role: string    | Tests whether the user has the specified role.                                                                    | `{% if user is assigned_role('editor') %}`                       |
| assigned_any_role  | test | roles: string[] | Tests whether the user has any of the specified roles. Equivalent to `assigned_role('a') or assigned_role('b')`.  | `{% if user is assigned_any_role('editor', 'administrator') %}`  |
| assigned_all_roles | test | roles: string[] | Tests whether the user has all of the specified roles. Equivalent to `assigned_role('a') and assigned_role('b')`. | `{% if user is assigned_all_roles('editor', 'administrator') %}` |

### Image
| Name         | Type   | Arguments                                                   | Description                                                                                                                                                                                                                                                                                         | Usage                                                                                                                                                        |
|--------------|--------|-------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| remora_image | filter | image: string, styles: string[], do_access_check: boolean   | Returns a responsive render array with the specified styles. If `do_access_check` is true, it will check if the user has access to the image file. _**default**_ is a required breakpoint! All breakpoints must match these Responsive [Image Styles](#image-styles) else they are silently ignored | `{{ image_uri\|remora_image({'default': 'card', 'md': 'portrait_3_4'}) }}`, this would render a card image up to md, after which it would switch to portrait |

### String
| Name    | Type   | Arguments   | Description                                          | Usage                   |
|---------|--------|-------------|------------------------------------------------------|-------------------------|
| slugify | filter | str: string | Replaces all non-alphanumeric characters with a dash | `{{ my_var\|slugify }}` |

### Remora include
The remora include tag will (when provided with a string and only a string) look for a template in the following locations:
- `directory ~ path ~ '--' ~ bundle '.html.twig'` (Check the subtheme has a CT specific override for the template)
- `bundle ~ '_content_type' ~ path ~ '.html.twig'` (Check the CT module supplies the template)
- `'@module_name' ~ path ~ '.html.twig'` (Check the module has the template)
- `directory ~ path ~ '.html.twig'` (Check the subtheme has a generic override for the template)
- `'@barrio_base_theme' ~ path ~ '.html.twig'` (Check the base theme has the template)

Where `bundle` is the node's bundle, and `path` is the path provided excluding the ".html.twig" extension. Example:
```twig
{% remora_include 'partials/content/hero.html.twig' %}
```
will look for the following templates, given that the subtheme is called `remora_subtheme` and we are on the page ct:
1. `themes/custom/remora_subtheme/templates/partials/content/hero--page.html.twig`
2. `web/modules/page_content_type/templates/partials/content/hero.html.twig`
3. `themes/custom/remora_subtheme/templates/partials/content/hero.html.twig`
4. `themes/custom/barrio_base_theme/templates/partials/content/hero.html.twig`

## Image styles
| Name            | Aspect ratio | Responsive image style | image style    |
|-----------------|--------------|------------------------|----------------|
| Card            | 4:3          | card                   | card           |
| Free            | Free         | free                   | free           |
| Landscape: 4x3  | 4:3          | landscape_4_3          | landscape_4x3  |
| Landscape: 16x9 | 16:9         | landscape_16_9         | landscape_16x9 |
| Landscape: 21x9 | 21:9         | landscape_21_9         | landscape_21x9 |
| Portrait 3x4    | 3:4          | portrait_3_4           | portrait_3x4   |
| Square 1x1      | 1:1          | square_1_1             | square_1x1     |
| Original size   | Free         | original_size          | original_size  |

## Plugins

### Referenceable entities

Remora core contains a plugin to provide a list of entity references on fields. This plugin is based off of Drupal's "Default" reference method.
The added functionality being that an event is fired when the field is rendered, allowing Content Types to opt-out of being referenceable. This list of Content Types is cached after initial
generation.

By default, all content types are listed as referenceable entities. To alter this list, the module providing the content type must implement an EventSubscriber. An example of this can be
found [here](docs/examples/referenceable-entities-subscriber.md).

### Media referenceable entities
There is a patch [here](./patches/media_library_build_ui_event.patch) which adds a BuildUIEvent to the media_library which is fired the when library is opened.
We subscribe to this event and filter down the media types to the list of referenceable entities we both allow.

#### Paragraph referenceable entities
We subscribe to the event supplied by the Layout Builder and filter down the media types to the list of referenceable entities we both allow.

#### Media referenceable entities
Unlike content and paragraph referenceable entities, the media referenceable list will be empty and populated dependent on which media types are available/installed.

### Module Configuration Files

Since we can expect that in the future Remora CTs that will be modular can potentially get new fields/config changes in newer releases, or
those can be custom expanded per-project basis, we needed to solve big issue with Drupal not recognizing updates in module configuration files.

Luckily, we can use Configuration Synchronizer module which makes snapshot of all installed extensions and scans for changes in module configurations.
Because of this, module needs to be in remora_core.

Few notes on the module:

- we don't even need to write update hooks for configuration with it

- changes can be made through Drupal UI, but also with drush function 'drush config-distro-update', both work fine

- in Drupal UI you can see what is going to change and also can cherry pick modules which you only want to update the configuration

- module allows 3 different types of doing update of config:

    - MERGE - merge updates into the site's active configuration while retaining any customizations made since the configuration was originally installed

    - PARTIAL UPDATE - available configuration updates to override any customizations you've made to those items

    - FULL RESET - discard all customizations made on your site and revert configuration to the state currently provided by installed modules

- MERGE is default one and matches our case completely

Simple example can be found [here](docs/examples/extendable-module-configuration.md).

### Module styling
See the [installation profile](https://github.com/MRM-Remora/installation_profile.git).
The decision was made to add the logic there, since the installation profile enforces the subtheme's structure through subtheme generation.

## File system

This module provides an extension to the Drupal file system by overriding `\Drupal::service('file_system')`.
When calling `\Drupal::service('file_system')->realpath()` you can now use a module or theme's machine name (like so: 'module_name://' or 'theme_name://') to get the absolute path to a module/theme or
files within a module/theme respectively.

## Repositories

This module provides a few repositories that are used a lot and would otherwise be registered as services in loads of projects/modules. These are listed below:

| Class                  | Service ID                             |
|------------------------|----------------------------------------|
| NodeRepository         | `remora_core.repository.node`          |
| TaxonomyTermRepository | `remora_core.repository.taxonomy_term` |
| UserRepository         | `remora_core.repository.user`          |

**Note:** The provided NodeRepository searches across all Content Types by default! To override this behaviour, extend the class and override the `getBaseSelect` method.


## Patches

| Title                                       | Description                                                                         | Link                                                                                                                           |
|---------------------------------------------|-------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| Access basic site information configuration | Access basic site information configuration if you have administer nodes permission | [open](https://github.com/MantaRayMedia/gists/blob/master/Patches/access_basic_site_information.patch)                         |
| Add a build UI event                        | Adds a BuildUIEvent to the media_library which is fired the when library is opened  | [open](./patches/media_library_build_ui_event.patch)                                                                           |
| Apply default crop                          | Applies a crop as soon as the image widget crop is loaded, if none is applied yet.  | [open](./patches/iwc_default_crop.patch)                                                                                       |
| Fix strnatcmp in LayoutBuilder              | Fixes an error around strnatcmp caused by the LayoutBuilder.                        | [open](https://www.drupal.org/files/issues/2023-12-04/3392572-30.patch)                                                        |
| Fix for form controls                       | Modal form actions broken once a subform has a validation error.                    | [open](https://raw.githubusercontent.com/MantaRayMedia/gists/master/Patches/layout_paragraphs_modal_form_actions_broken.patch) |
| Fix for taxonomy access                     | Non administrator roles can not see access to `taxonomy overview`                   | [open](https://www.drupal.org/files/issues/2024-02-15/3415709-permission-fix.patch)                                            |
| Supress warnings for missing `_core`        | Not showing warnings when doing config_merge and MRM modules don't have it          | [open](./patches/config_merge-supress-warnings.patch)                                                                          |
| Broken link reports                         | Override the broken link reports view to only show the field if it isn't nid        | [open](./patches/linkchecker.patch)                                                                                            |

### Broken link checker
The broken link checker module does not support paragraphs. This is an issue for us, since we use paragraphs extensively.
We override their linkExtractor services with our own. When a node is being processed, we get the node's rendered output from SOLR and use that to extract any links. Completely ignoring whatever settings may be present on the field.
Any other entity type will use the default linkExtractor service and its configured behaviour.

## Updates
Update version numbering will match the release version. E.g. if next release is v0.0.4 and there is already an update function for that version (remora_core_update_9004) then add it into there, otherwise you can create a new update function for that version

| Update version | Description                                                                          |
|----------------|--------------------------------------------------------------------------------------|
| 9001           | Install dblog and admin_toolbar_tools, added after the initial release               |
| 9004           | Install gin_toolbar. Enable the option to allow users to override gin theme settings |


## Toggle field visibility
There are two ways to target the field that will be toggling the visibility depending on the field/widget type used.
Here are examples for 4 options:
````
checkbox | toggle = `:input[name="field_test[value]"]' => ['checked' => true]`
select | radio = `:input[name="field_test"]' => ['value' => 'yes']`
````


## Commands
Available Drush commands

| Command                | Alias | Description                              |
|------------------------|-------|------------------------------------------|
| remora:library:install | rli   | Install specific library from zip files  |


## Theme dependencies on modules

In case like we had with barrio_base_theme where it depends on module cards_nugget, we have problematic situation since Drupal themes on install don't automatically install their dependencies. In that case, dpendency should be added to the remora_core which is installation profile, but if the dpendency that we are adding is dependent on the remora_core (cause it holds all the field storages), we have to manually install the module in install function of remora_core since that one is triggered after the configuration is imported and all dependencies from remora_core.info installed.
