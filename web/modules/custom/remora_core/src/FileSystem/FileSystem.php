<?php

namespace Drupal\remora_core\FileSystem;

use Drupal;
use Drupal\Core\File\FileSystem as BaseFileSystem;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Psr\Log\LoggerInterface;

class FileSystem extends BaseFileSystem
{

  private const SCHEME_SEPARATOR = '://';

  private Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler;
  private Drupal\Core\Extension\ThemeHandlerInterface $themeHandler;

  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager, Settings $settings, LoggerInterface $logger)
  {
    parent::__construct($stream_wrapper_manager, $settings, $logger);
    $this->moduleHandler = Drupal::moduleHandler();
    $this->themeHandler = Drupal::service('theme_handler');
  }

  /**
   * {@inheritDoc}
   */
  public function realpath($uri): string|false
  {
    // if the URI is not a string, cry (Drupal still doesn't use type hinting :( )
    if(!is_string($uri)) {
      return false;
    }

    // if uri is formatted as xxx://yyy/zzz, we will handle it
    if(preg_match('/.*:\/\/.*/', $uri)) {
      return $this->handleSchemeRealpath($uri);
    }

    // otherwise let the parent handle it
    return parent::realpath($uri);
  }

  /**
   * Handles any URI that is formated as xxx://yyy/zzzz
   *
   * @param string $uri The URI scheme to resolve
   * @return string|false The absolute path to the file/folder, or false on failure
   */
  private function handleSchemeRealpath(string $uri): string|false
  {
    // check if the scheme is a reserved keyword, if so, turn to the parent to resolve the path
    [$scheme] = $this->splitUri($uri);
    if(in_array($scheme, ['public', 'private', 'temporary'])) {
      return parent::realpath($uri);
    }

    // if the module returned a valid path, use it
    if(($moduleRealpath = $this->getModuleRealpath($uri)) !== false) {
      return $moduleRealpath;
    }

    // last option we have, check if the theme exists
    return $this->getThemeRealpath($uri);
  }

  /**
   * Returns the realpath to the URI given the module exists
   *
   * @param string $uri The URI for the file, e.g. remora_core://README.md
   * @return string|false The absolute path to the file
   */
  private function getModuleRealpath(string $uri): string|false
  {
    [$scheme, $path] = $this->splitUri($uri);

    // if not public/private and the module exists, replace "module_name://" with the absolute path to the module
    if($this->moduleHandler->moduleExists($scheme)) {
      $uri = sprintf('%s/%s', $this->moduleHandler->getModule($scheme)->getPath(), $path);
    }

    return parent::realpath($uri);
  }

  /**
   * Returns the realpath to the URI given the theme exists
   *
   * @param string $uri The URI for the file, e.g. barrio_base_theme://README.md
   * @return string|false The absolute path to the file
   */
  private function getThemeRealpath(string $uri): string|false
  {
    [$scheme, $path] = $this->splitUri($uri);

    // if not public/private and the module exists, replace "theme_name://" with the absolute path to the module
    if($this->themeHandler->themeExists($scheme)) {
      $uri = sprintf('%s/%s', $this->themeHandler->getTheme($scheme)->getPath(), $path);
    }

    return parent::realpath($uri);
  }

  /**
   * Splits a scheme URI into the scheme and the path, e.g.
   *     remora_core://src/FileSystem/FileSystem.php => ['remora_core', 'src/FileSystem/FileSystem.php']
   *
   * @param string $uri The URI to split
   * @return array An array containing the schema and the path
   */
  private function splitUri(string $uri): array
  {
    $schemeStrlen = strpos($uri, self::SCHEME_SEPARATOR);

    // get the part before ://
    $scheme = substr($uri, 0, $schemeStrlen);
    $path = substr($uri, $schemeStrlen + 3);

    return [$scheme, $path];
  }

}
