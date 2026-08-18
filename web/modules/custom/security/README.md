# security
Security bundle that contains default configuration for seckit and permissionspolicy, as well as our own middleware


## Middleware
- The module provides a middleware that will remove any configured HTTP headers. By default it removes most (if not all) x-drupal headers. A configuration form is provided to update the list of headers to remove.
- The middleware can also add static HTTP headers.
- The module also provides a hook to remove metatags `security_page_attachments_alter` from the head. The same configuration form can be used to update the list of metatags to remove.
- Adds `Clear-Site-Data` header on user login/logout.

## Hooks
| Hook               | Description                                                           |
|--------------------|-----------------------------------------------------------------------|
| `hook_user_login`  | Adds attribute `_security.user_logged_in` to the request attributes.  |
| `hook_user_logout` | Adds attribute `_security.user_logged_out` to the request attributes. |


## Drush
| Command               | Short | Description                                                  |
|-----------------------|-------|--------------------------------------------------------------|
| `sql:dump-anonymize`  | `sqa` | Custom Drush command to dump SQL with anonymization process. |
