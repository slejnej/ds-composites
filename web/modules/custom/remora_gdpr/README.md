# remora_gdpr

### Recommended modules
The below modules are installed by this module. Remora_core functions without them, they're just used on a lot of projects. Some projects might opt to use other analytics tools like piwikpro, however.

| Module name                     | Version | Extended README                                                           | Comments                                                                                                             |
|---------------------------------|---------|---------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------|
| drupal/eu_cookie_compliance     | ^1.24   |                                                                           | Cookie compliance banner                                                                                             |
| drupal/eu_cookie_compliance_gtm | ^2.1    |                                                                           | Support for single GTM                                                                                               |
| drupal/google_tag | ^2.1    |                                                                           | Google Tag functionality                                                                                           |

## Patches

| Title                                       | Description                                                                         | Link                                                                                                                           |
|---------------------------------------------|-------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| EU Cookie compliance remove settings                        | Removes settings file from module eu_cookie_compliance                  | [open](https://raw.githubusercontent.com/MRM-Remora/remora_gdpr/develop/patches/eu_cookie_compliance_settings.patch)                            |
| GTM new consent mode                        | Adds capability to parse json data and pass it to GTM consent mode                  | [open](https://raw.githubusercontent.com/MantaRayMedia/gists/master/Patches/gtm_consent_mode.patch)                            |
| GTM remove settings                       | Removes settings file from module google_tag                  | [open](https://raw.githubusercontent.com/MRM-Remora/remora_gdpr/develop/patches/google_tag_settings.patch)                            |

### Hooks
- `hook_preprocess_HOOK`: changes the youtube to youtube-nocookie
- `hook_oembed_resource_url_alter`: adds dnt query parameter to vimeo
- `hook_theme_suggestions_HOOK_alter`: adds possibility to override the template for cookie banner
- `hook_theme`: template instructions for overriding when template is _eu_cookie_compliance_popup_info_


## Styling
### Variables
| Variable                               | Description                                                     | Default                          |
|----------------------------------------|-----------------------------------------------------------------|----------------------------------|
| `$cookie_bg`                           | Background colour for cookie banner                             | $global-color-light-100    |
| `$cookie_title_color`                  | Color of main title in cookie banner                            | $text-primary                    |
| `$cookie_text_color`                   | Color of text in cookie banner                                  | $text-secondary                  |
| `$cookie_link_color`                   | Color of links in cookie banner                                 | $text-link                       |
| `$cookie_link_hover_color`             | Color of link hovers in cookie banner                           | $text-link-hover                 |
| `$cookie_modal_radius`                 | Border radius for 'Cookie settings' modal popup                 | $global-border-radius-sm  |
| `$cookie_modal_padding`                | Padding for 'Cookie settings' modal popup                       | 40px                             |
| `$cookie_modal_gap`                    | Gap between elements in Cookie banner and modal popup           | 20px                             |
| `$cookie_modal_shadow`                 | Shadow for 'Cookie settings' modal popup                        | 5px 5px 10px rgba(0, 0, 0, 0.25) |
| `$cookie_modal_header_bg`              | Background color of header in 'Cookie settings' modal popup     | $global-color-black-white        |
| `$cookie_modal_header_color`           | Color of header text in 'Cookie settings' modal popup           | $text-primary                    |
| `$cookie_modal_link_color`             | Color of accordion title in 'Cookie settings' modal popup       | $text-link                       |
| `$cookie_modal_link_hover_color`       | Hover color of accordion title in 'Cookie settings' modal popup | $text-link-hover                 |
| `$cookie_modal_toggle_disabled_bg`     | Background color of disabled toggle                             | $global-color-black-100    |
| `$cookie_modal_toggle_disabled_icon`   | Icon color of disabled toggle                                   | $global-color-black-balck-300    |
| `$cookie_modal_toggle_disabled_border` | Border color of disabled toggle                                 | $global-color-black-balck-300    |
| `$cookie_modal_toggle_default_icon`    | Icon color of default toggle                                    | $btn-primary-background          |
| `$cookie_modal_toggle_checked_icon`    | Icon color of toggle when it'd been checked/enabled             | $btn-primary-hover-background    |
| `$cookie_modal_toggle_default_border`  | Border color of default toggle                                  | $global-color-black-balck-300    |
