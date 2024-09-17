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
