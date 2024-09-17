# Remora 3 base theme

This is a [Bootstrap 5](https://www.drupal.org/project/bootstrap5) subtheme.  
This theme is used as a base on all our Remora sites. It contains the majority of the styling for basic Remoras. Templates can be extended and overridden using a subtheme. The base theme contains a
lot of customization - which can easily be made more on-brand for a client using variables in the subtheme.

## Config
### Favicon
Favicons are read automatically from the subtheme's `/favicon.ico` file.

### Breadcrumbs
Breadcrumbs are enabled on every page except `<front>`. This is configured on the block and can be overriden per site after installation.

## Creating a subtheme

The subtheme is created during the installation of Drupal website.    
More information on how to enable the installation profile and setting up a subtheme can be found [here](https://github.com/MRM-Remora/remora_installation_profile).
Folder `subtheme` contains all files that will be generated in new sub-theme and are pointing to components in the `assets/scss`.
In order to `override` any component, replicate structure inside generated `sub-theme` and `import` generated files inside `_components_override.scss` under the comment inside generated file! 

### Custom variables
Custom variables that do not come from figma can be added to `variables/_custom.scss`.

### Fonts
1. Create a folder within assets called `fonts`, all font files should be added here.
2. Add the following codesnippet to the subtheme's `variables/_custom.scss` and update the font variables with the subtheme specific fonts.
```scss
$example-font: 'example-font';

$heading-font-family: $example-font;
$body-font-family: $example-font;
$text-large-font-family: $example-font;
$button-font-family: $example-font;
$card-header-font-family: $example-font;
$nav-font-family: $example-font;
$nav-sub-font-family: [font-name];

```
3. Add the following codesnippet to the subtheme's `_components_override.scss` and update the font variables with the subtheme specific fonts.
```scss
/**     FONTS    **/
@font-face {
font-family: [example_font];
src: url('../assets/fonts/[example_font]') format('truetype');
}
```

## Gulp

Gulp is used to compile the SCSS to CSS, and run minifiers against the various asset files.  
All js and css assets are stored in build/js and build/css respectively.  
Current `Node 18` is required, however once lando supports `Node 20` this theme can be upgraded immediately without further configuration.  

### CleanCSS
We copied [gulp-clean-css](https://www.npmjs.com/package/gulp-clean-css) to our project to minify the css files.  
This was done because gulp-clean-css is locked to clean-css@4 which contains bug preventing us from using @layer and @container queries.

### Usage

To compile, run the following command in the subtheme's directory: `npm install && gulp`.  
To omit sourcemap file generation, add the `--production` flag to `gulp`. 

## Structure

### Stylesheets

The (S)CSS files have been split into two separate files. One for layout (bootstrap.scss) and one for general theming (style.scss). This is done so the two files can be compiled separately and
requested separately by Drupal as needed.

**NOTE**: All stylesheets in the root SCSS directory that are NOT prefixed with an underscore (`_`) will automatically be compiled into its own css file. 

## Release

Before committing to `git`, bump the version using semantic versioning:

- patch: `./node_modules/.bin/grunt bump` (ie 0.0.2)
- minor: `./node_modules/.bin/grunt bump:minor` (ie 0.1.2)
- major: `./node_modules/.bin/grunt bump:major` (ie 1.0.0)

## Browserslist

Currently supported browsers - [browserslist](https://browsersl.ist/#q=%3E+1%25+%2Csupports+es6-module).  
In our `.browserslistrc` file, we support the same browsers Bootstrap5 supports as described [here](https://github.com/twbs/bootstrap/blob/v5.0.2/.browserslistrc).
This file is automatically picked up by autoprefixer to generate the required css pseudo-selectors
