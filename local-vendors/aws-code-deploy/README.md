# AWS CodeDeploy
Files needed for code deploy. This is a `template` of files that are moved to correct folders in project with use of
`mrm-remora/aws-plugin-installer`.

## Installation
In main project where you want to use this functionality, add to `composer.json`:
```json
{
  "require": {
    "mrm-remora/aws-code-deploy": "^1.0"
  },
  "repositories": [
    {
      "name": "mrm-remora/aws-code-deploy",
      "type": "vcs",
      "url": "git@github.com:MRM-Remora/aws-code-deploy.git",
      "no-api": true
    }
  ],
  "config": {
    "allow-plugins": {
      "mrm-remora/aws-code-deploy": true
    }
  }
}
```

## Overriding
All `bash script` files that are not moved (`aws` folder), can be overriden by creating file as per example:
To override file `assets/aws/hooks/AfterInstall.sh`, which will be located in `project` at `SOME_PROJECT/vendor/mrm-remora/aws-code-deploy/assets/aws/hooks/AfterInstall.sh` should be placed to `SOME_PROJECT/aws/hooks/AfterInstall.sh` to be overridden.

## settings.APP_NAME.php

If you need project specific settings, you need to add settings.APP_NAME.toml and settings.APP_NAME.php.tmpl files at `APP_NAME/aws/default/conf.d` and `APP_NAME/aws/default/templates`. 
Have in mind that all settings from settings.local.php you can override in that project settings file. :)
