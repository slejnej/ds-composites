# AWS Plugin Installer
This is a custom installer for any composer package that has `type: "mrm-template"`.

## Configuration
Plugin can do two things: `move` files and folders and `replace` variables in files and folders.
Both are defined by adding configuration to `composer.json` of `type` as above.
```json
{
  // configuration above
  "extra": {
    "installer-logging": true,
    "move-paths": [
      {"some-path/the_file.txt": "/somewhere/any_file_name.txt"},
      {"assets/web": "/web"},
      {"assets/root":  "/"}
    ],
    "replace-variables": [
      {"assets": {"app_name": "project_name"}},
      {"assets": {"APP_NAME": "PROJECT_NAME"}},
      {"assets": {"RANDOM_PORT_NUMBER": "random_port"}}
    ]
  }
}
```

__"installer-logging"__ definition:
- is a `bool` variable
- if enabled it will create a log file in `root` project (plugin-installer_DATE.log), logging `replaced variables` and `moved files` for each plugin

__"move-paths"__ definition:
- `{from_folder: to_folder}`
- `{from_file_in_a_folder: to_file}`
- if `destination` folder starts with `/` this will place it on the `root` of the project where `plugin` is installed

__"replace-variables"__ definition:
- `{folder_to_serach_for_string: {string_to_search: replace_with}}`
- `replace_with` string can be anything or predefined string variables:
  - `project_name`, that checks `folder-name` of installed project
      - if structure is `/apps/Some-Project/composer.json`, then the `project_name` will translate to `some-project`
      - if structure is `/apps/Some-Project/composer.json`, then the `PROJECT_NAME` will translate to `Some-Project`
  - `port_number` which will replace the string with random port between `6000` and `65535`
  - `theme_name` will look for directory in `/web/themes/custom` and if nothing there then will use `RBT`
    - variable is replaced with `/themes/custom/NAME` or `/themes/contrib/RBT`


## Installation
In main project where you want to use this functionality, add to `composer.json`:
```json
{
  "require": {
    "mrm-remora/aws-plugin-installer": "^1.0"
  },
  "repositories": [
    {
      "name": "mrm-remora/aws-plugin-installer",
      "type": "vcs",
      "url": "git@github.com:MRM-Remora/aws-plugin-installer.git",
      "no-api": true
    }
  ],
  "config": {
    "allow-plugins": {
      "mrm-remora/aws-plugin-installer": true
    }
  }
}
```
