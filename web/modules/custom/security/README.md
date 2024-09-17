# security
Security bundle that contains default configuration for seckit and permissionspolicy, as well as our own middleware


## Middleware
The module provides a middleware that will remove any configured HTTP headers. By default it removes most (if not all) x-drupal headers. A configuration form is provided to update the list of headers to remove.  
The module also provides a hook to remove metatags `security_page_attachments_alter` from the head. The same configuration form can be used to update the list of metatags to remove.
