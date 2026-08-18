<?php

namespace Drupal\installation_profile\Manager;

use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\installation_profile\Util\StringUtil;
use JetBrains\PhpStorm\ObjectShape;
use stdClass;

class ModuleScssManager
{

  /** @var string The line after which we will add the _modules.scss import */
  private const PRE_IMPORT_LINE = "@import 'variables_drupal';";

  private readonly string $activeTheme;
  private readonly string $themePath;

  public function __construct(private readonly FileSystemInterface $fileSystem)
  {
    $this->activeTheme = Drupal::configFactory()->get('system.theme')->get('default');
    $this->themePath = $this->fileSystem->realpath("{$this->activeTheme}://assets/scss");
  }

  /**
   * Link the given module's style.scss and layout.scss file to the subtheme by adding an import statement to the _modules.scss file
   *
   * @param string $moduleName The module to link the style.scss file for
   * @return bool Whether any linking has happened
   */
  public function linkStyles(string $moduleName): bool
  {
    $moduleScssPath = $this->fileSystem->realpath("$moduleName://scss");
    if(!$moduleScssPath || !is_readable($moduleScssPath)) {
      return false;
    }

    // if not a custom theme, abort
    if(!str_contains($this->themePath, '/themes/custom/')) {
      return false;
    }

    $hasAddedScss = false;
    $scssFiles = $this->fileSystem->scanDirectory($moduleScssPath, '/^[^_].*\.scss$/', ['recurse' => false]);
    foreach($scssFiles as $file) {
      $hasAddedScss = $this->linkStylesheet($moduleName, $file) || $hasAddedScss;
    }


    return $hasAddedScss;
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

  /**
   * Adds a link to the module's stylesheet to the matching xxx.scss file
   * For example, if the filename is style.scss, in the subtheme the file will be modules/_style.scss and style.scss
   *
   * @param string $moduleName
   * @param stdClass $file
   * @return bool
   */
  private function linkStylesheet(string $moduleName, #[ObjectShape(['filename' => 'string', 'uri' => 'string', 'name' => 'string'])] stdClass $file): bool
  {
    $targetFilename = "_{$file->name}.scss";

    $styleFile = $this->fileSystem->realpath("$moduleName://scss/{$file->filename}");
    if(!$styleFile || !is_readable($styleFile)) {
      return false;
    }

    // get the active theme
    $modulesScssPath = "$this->themePath/modules/$targetFilename";

    // if the xxx.scss file exists and already contains the module, abort successfully
    if(file_exists($modulesScssPath)) {
      $modulesScss = file_get_contents($modulesScssPath);
      if(str_contains($modulesScss, $moduleName)) {
        return true;
      }
    }

    // The first character that is different between the two paths
    $relativePath = StringUtil::createRelativePath($this->themePath . '/modules', $styleFile);

    $importStatement = PHP_EOL . "@import '$relativePath';" ;

    $this->createModulesScss($modulesScssPath, $file);

    // add the import statement to the modules/xxx.scss file
    file_put_contents($modulesScssPath, $importStatement, FILE_APPEND);

    return true;
  }

  /**
   * Creates an import rule for the file
   * Creates the root xxx.scss file if it doesn't exist
   * Create the modules/_xxx.scss file if it doesn't exist
   *
   * @param string $modulesScssPath
   * @param stdClass $file
   * @return void
   */
  private function createModulesScss(string $modulesScssPath, stdClass $file): void
  {
    $rootScssFile = "$this->themePath/{$file->name}.scss";

    // if the modules/xxx.scss file doesn't exist, create it and add the import statement to style.scss
    if(!file_exists($modulesScssPath)) {
      $style = @file_get_contents($rootScssFile) ?: '';
      $modulesImport = PHP_EOL . "@import 'modules/{$file->name}';";

      if(str_contains($style, self::PRE_IMPORT_LINE)) {
        $style = str_replace(self::PRE_IMPORT_LINE, self::PRE_IMPORT_LINE . $modulesImport, $style);
        file_put_contents($rootScssFile, $style);
      } else {
        file_put_contents($rootScssFile, $modulesImport, FILE_APPEND);
      }
    }
  }

}
