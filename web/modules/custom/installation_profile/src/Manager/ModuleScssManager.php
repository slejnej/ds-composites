<?php

namespace Drupal\installation_profile\Manager;

use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\installation_profile\Util\StringUtil;

class ModuleScssManager
{

  /** @var string The line after which we will add the _modules.scss import */
  private const PRE_IMPORT_LINE = "@import 'variables_drupal';";

  public function __construct(private readonly FileSystemInterface $fileSystem)
  {
  }

  /**
   * Link the given module's style.scss file to the subtheme by adding an import statement to the _modules.scss file
   *
   * @param string $moduleName The module to link the style.scss file for
   * @return bool Whether any linking has happened
   */
  public function linkStyles(string $moduleName): bool
  {
    $styleFile = $this->fileSystem->realpath("$moduleName://scss/style.scss");
    if(!$styleFile || !is_readable($styleFile)) {
      return false;
    }

    // get the active theme
    $activeTheme = Drupal::configFactory()->get('system.theme')->get('default');
    $themePath = $this->fileSystem->realpath("$activeTheme://assets/scss");
    $modulesScssPath = "$themePath/_modules.scss";

    // if not a custom theme, abort
    if(!str_contains($themePath, '/themes/custom/')) {
      return false;
    }

    // if the _modules.scss file exists and already contains the module, abort successfully
    if(file_exists($modulesScssPath)) {
      $modulesScss = file_get_contents($modulesScssPath);
      if(str_contains($modulesScss, $moduleName)) {
        return true;
      }
    }


    // The first character that is different between the two paths
    $relativePath = StringUtil::createRelativePath($themePath, $styleFile);

    $importStatement = "@import '$relativePath';";

    // if the _modules.scss file doesn't exist, create it and add the import statement to style.scss
    if(!file_exists($modulesScssPath)) {
      $style = file_get_contents("$themePath/style.scss");
      $style = str_replace(self::PRE_IMPORT_LINE, self::PRE_IMPORT_LINE . "\n@import '_modules';", $style);
      file_put_contents("$themePath/style.scss", $style);
    }

    // add the import statement to the _modules.scss file
    file_put_contents($modulesScssPath, $importStatement, FILE_APPEND);

    return true;
  }

  /**
   * Removes the given module's import statement from _modules.scss
   *
   * @param string $moduleName The module to unlink the style.scss file for
   * @return void
   */
  public function unlinkStyles(string $moduleName): void
  {
    $activeTheme = Drupal::configFactory()->get('system.theme')->get('default');
    $modulesFile = $this->fileSystem->realpath("$activeTheme://assets/scss/_modules.scss");
    if(!$modulesFile) {
      return;
    }

    $modulesScss = file_get_contents($modulesFile);
    $modulesScss = preg_replace("/^@import '[a-z0-9.\/]+\/$moduleName\/[a-z0-9.\/]+';$/", '', $modulesScss);
    file_put_contents($modulesFile, $modulesScss);
  }

}
