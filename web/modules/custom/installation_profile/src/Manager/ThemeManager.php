<?php

namespace Drupal\installation_profile\Manager;

use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\installation_profile\Exception\PathExistsException;
use Drupal\installation_profile\Util\StringUtil;

/**
 * Handles the creation of (sub)themes and its files
 */
class ThemeManager
{

  private const BASE_THEME_MACHINE_NAME = 'barrio_base_theme';
  private const BASE_THEME_NAME = 'Remora base theme';

  private readonly FileSystemInterface $fileSystem;
  /**
   * @var string Absolute path to the subtheme's directory
   */
  private readonly string $themePath;
  private array $files = [];

  /**
   * @var string Line to be searched in files to combine code before and after
   */
  private const CODE_COMBINE_LINE = "// custom code below this line";
  private const EXTRACT_FROM_EXISTING = ['_components_override.scss'];

  /**
   * @param string $machineName The theme's machine name
   * @param string $humanReadableName The theme's human-readable name
   */
  public function __construct(private readonly string $machineName, private readonly string $humanReadableName)
  {
    $this->fileSystem = Drupal::service('file_system');

    // check if the theme already exists, if so use that one
    $themePath = $this->fileSystem->realpath($this->machineName . '://');
    if ($themePath !== false) {
      $this->themePath = $themePath;
    } else {
      // create a new theme in themes/custom
      $customThemesPath = sprintf('%s/%s', DRUPAL_ROOT, 'themes/custom');
      $this->themePath = sprintf('%s/%s', $customThemesPath, $this->machineName);
    }

  }

  /**
   * Initializes all the files required for the most basic Remora subtheme
   *
   * @return $this
   * @throws PathExistsException Thrown if the subtheme already exists, an existing subtheme can not be re-initialized
   */
  public function init(): self
  {
    if (is_dir($this->themePath)) {
      throw new PathExistsException($this->themePath);
    }

    $this->initReadme();
    $this->initScss();
    $this->initJs();
    $this->initDependencies();
    $this->initDrupalFiles();

    return $this;
  }

  /**
   * Write the generated files to the filesystem
   *
   * @return bool Whether all the files were successfully written
   */
  public function write(): bool
  {
    if (!is_dir($this->themePath)) {
      $this->fileSystem->mkdir($this->themePath, recursive: true);
    }

    return $this->dump($this->files);
  }

  /**
   * Adds bootstrap variables
   *
   * @param array $variables
   * @return $this
   */
  public function addScssFiles(array $files): self
  {
    foreach ($files as $fileName => $contents) {
      $file = sprintf('_%s.scss', $fileName);
      $this->files['assets']['scss']['variables'][$file] = $contents;
    }

    return $this;
  }

  /**
   * Adds a very plain README file
   *
   * Generates:
   *   - README.md
   *
   * @return void
   */
  private function initReadme(): void
  {
    $this->files['README.md'] = <<<EOT
# $this->humanReadableName theme
This subtheme is based off of Remora's base theme. An extended documentation is available [here](https://www.github.com/MRM-Remora/barrio_base_theme/tree/master/README.md)

## Gulp
Gulp is used to compile the SCSS to CSS, and run minifiers against the various asset files.  
All js and css assets are stored in build/js and build/css respectively.

### Usage
To compile, run the following command in the subtheme's directory: `npm install && gulp`

EOT;
  }

  /**
   * Adds the SCSS files required for a base theme
   *
   * Generates:
   *   - assets/scss/_variables_bootstrap.scss
   *   - assets/scss/_variables_drupal.scss
   *   - assets/scss/bootstrap.scss
   *   - assets/scss/style.scss
   *
   * @return void
   */
  public function initScss(): void
  {
    $baseThemeName = self::BASE_THEME_MACHINE_NAME;
    $remoraBasePath = $this->fileSystem->realpath("$baseThemeName://");

    // The first character that is different between the two paths
    $overlappingPath = StringUtil::getOverlap($remoraBasePath, $this->themePath);

    // make sure the path ends on a slash, so the c in 'contrib' and 'custom' doesn't mess up the algo
    $overlappingPath = preg_replace('/[^\/]+$/', '', $overlappingPath);
    $overlapIndex = strlen($overlappingPath);

    // The path with the overlapping absolute path stripped
    $nonOverlapBase = substr($remoraBasePath, $overlapIndex);

    $this->files['assets']['scss'] = [];

    // location of subtheme folder inside base, where it will extract all the scss files
    $readSubThemeDir = sprintf('%s%s/subtheme', $overlappingPath, $nonOverlapBase);

    if (file_exists($readSubThemeDir)) {
      // you can have up to 3 subdirectories, deal with it
      $files = glob(sprintf("%s/{,*/,*/*/,*/*/*/}*.scss", $readSubThemeDir), GLOB_BRACE);

      foreach ($files as $file) {
        $name = str_replace("$readSubThemeDir/", '', $file);

        // save source content as default
        $content = file_get_contents($file);

        // create the subdirectory in the subtheme if it doesn't exist yet
        $dirname = dirname($name);
        $subThemeDirname =  sprintf("%s/assets/scss/%s", $this->themePath, $dirname);

        if($dirname !== '.' && !file_exists($subThemeDirname)) {
          mkdir($subThemeDirname, recursive: true);
        }

        if (in_array($name, self::EXTRACT_FROM_EXISTING)) {
          // check if theme already has this file then do the merging
          $themeHasFile = sprintf("%s/assets/scss/%s", $this->themePath, $name);
          if (file_exists($themeHasFile)) {
            $tmp = file_get_contents($themeHasFile);
            $pos = strpos($tmp, self::CODE_COMBINE_LINE);

            if ($pos !== false) {
              $destContent = substr($tmp, ($pos + strlen(self::CODE_COMBINE_LINE)));
              $content .= $destContent;
            } else {
              $content = $tmp;
            }
          }
        }

        $this->files['assets']['scss'][$name] = $content;

        // do not override with empty content
        if (empty($content)) {
          // check if file there, if so, then not override it, else do crete empty file as might be needed
          $themeHasFile = sprintf("%s/assets/scss/%s", $this->themePath, $name);
          if (file_exists($themeHasFile)) {
            unset($this->files['assets']['scss'][$name]);
          }
        }
      }
    }
  }

  /**
   * Adds a general purpose JS file for the theme
   *
   * Generates:
   *   - assets/js/theme_name_global.js
   *
   * @return void
   */
  private function initJs(): void
  {
    $jsName = sprintf('%s_global', $this->machineName);

    $this->files['assets']['js'] = [
      "global.js" => <<<EOT
(function($, Drupal) {
  'use strict';

  Drupal.behaviors.$jsName = {
    attach: function(context, settings) {
      if (context !== document) {
        return;
      }

    }
  };

})(jQuery, Drupal);
EOT
    ];
  }

  /**
   * Adds the files required for installing dependencies
   * Generates:
   *   - .nvmrc
   *   - .browserslistrc
   *   - .gitignore
   *   - package.json
   *   - package-lock.json
   *   - gulpfile.mjs
   *
   * @return void
   */
  private function initDependencies(): void
  {
    $filesToCopy = ['.gitignore', '.nvmrc', '.browserslistrc', 'package.json', 'package-lock.json', 'gulpfile.mjs'];
    foreach ($filesToCopy as $file) {
      $pathToBaseFile = sprintf("%s://%s", self::BASE_THEME_MACHINE_NAME, $file);
      $this->files[$file] = file_get_contents($this->fileSystem->realpath($pathToBaseFile));
    }

    foreach (['package.json', 'package-lock.json'] as $packageFile) {
      // replace the reference to the base theme with our own theme
      $this->files[$packageFile] = str_replace(
        [self::BASE_THEME_MACHINE_NAME, self::BASE_THEME_NAME],
        [$this->machineName, $this->humanReadableName],
        $this->files[$packageFile]
      );
    }
  }

  /**
   * Adds the files required by Drupal to the files list
   * Generates:
   *   - theme_name.info.yml
   *   - theme_name.libraries.yml
   *
   * @return void
   */
  private function initDrupalFiles(): void
  {
    $baseThemeMachineName = self::BASE_THEME_MACHINE_NAME; // so we can use it in the HEREDOC
    $this->files[sprintf('%s.info.yml', $this->machineName)] = <<<EOT
name: '$this->humanReadableName'
type: theme
version: 0.0.1
description: '$this->humanReadableName subtheme based on Remora base theme.'
core_version_requirement: '^9.4 || ^10'
'base theme': $baseThemeMachineName
libraries:
  - $this->machineName/global-styling
  - $this->machineName/global-javascript
libraries-override:
  $baseThemeMachineName/global-styling: true
regions:
  header: Header
  nav_branding: 'Navigation branding region'
  nav_main: 'Main navigation region'
  nav_additional: 'Additional navigation region (eg search form, social icons, etc)'
  breadcrumb: Breadcrumbs
  content: 'Main content'
  sidebar_first: 'Sidebar first'
  sidebar_second: 'Sidebar second'
  footer: Footer
EOT;

    $this->files[sprintf('%s.libraries.yml', $this->machineName)] = <<<EOT
global-styling:
  css:
    theme:
      build/css/style.min.css: {}

global-javascript:
  js:
    build/js/global.min.js: {}
  dependencies:
    - core/jquery
    - core/drupal

EOT;
    $this->files['screenshot.png'] = file_get_contents($this->fileSystem->realpath(self::BASE_THEME_MACHINE_NAME . '://screenshot.png'));

  }

  /**
   * Dumps the files to the filesystem recursively
   *
   * @param array $files The files to write
   * @param array $path The path within the subtheme to write to. E.g. scss files would have path ['assets', 'scss']
   * @return bool Whether all the files were written successfully
   */
  private function dump(array $files, array $path = []): bool
  {
    $result = true;

    foreach ($files as $filename => $file) {
      if (is_array($file)) {
        $result &= $this->dump($file, [...$path, $filename]);
      } else {
        $dir = sprintf('%s/%s', $this->themePath, implode('/', $path));

        if (!is_dir($dir)) {
          $this->fileSystem->mkdir($dir, recursive: true);
        }

        $result &= file_put_contents(sprintf('%s/%s', $dir, $filename), $file) !== false;
      }
    }

    return (bool)$result;
  }
}
