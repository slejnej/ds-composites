<?php

namespace Drupal\remora_core\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
class BootstrapIconExtension extends AbstractExtension
{

  public function getFunctions(): array
  {
    return [
      new TwigFunction('get_bs_icon_path', [$this, 'getBSIconPath']),
    ];
  }

  /**
   * Returns the full path string needed to embed bootstrap icon sprites in twig template
   *
   * @param string $iconName The icon name (e.g. cloud-download-fill)
   * @return string full path
   */
  public function getBSIconPath(string $iconName): string
  {
    return sprintf('/themes/custom/barrio_base_theme/node_modules/bootstrap-icons/bootstrap-icons.svg#%s', $iconName);
  }

}
