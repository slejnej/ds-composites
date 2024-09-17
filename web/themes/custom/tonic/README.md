# Tonic admin theme
This is the admin theme for Remora 3. It is based on the [Gin](https://www.drupal.org/project/gin) admin theme.

## Usage
This theme will need to be installed and enabled but should not be set as admin theme in appearance settings.

There is a hook_page_attachments on remora_core that will attach the global-styling library from this theme to all admin pages so we are able to customise the Gin theme

There is also a template service called in remora_core that will enable all template files added in this theme to override template where needed for admin theme.

## Dependencies
- remora_core