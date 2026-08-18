<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Adds extensions for easier asset retrieval
 */
class AssetsExtension extends AbstractExtension
{

  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly ThemeManagerInterface $themeManager
  )
  {
  }

  public function getFunctions(): array
  {
    return [
      new TwigFunction('asset', [$this, 'getAssetPath']),
    ];
  }

  /**
   * Returns the full path to an asset for usage with images
   *
   * @param string $path The path to the asset relative to build/assets
   * @return string|null The full path, or null if the file doesn't exist
   */
  public function getAssetPath(string $path): ?string
  {
    $activeTheme = $this->themeManager->getActiveTheme();
    return substr($this->fileSystem->realpath(sprintf('%s://build/assets/%s', $activeTheme->getName(), $path)) ?: '', strlen(DRUPAL_ROOT));
  }

}
