<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\remora_core\Util\StringUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
class StringExtension extends AbstractExtension {

  public function getFilters(): array
  {
    return [
      'slugify' => new TwigFilter('slugify', [$this, 'slugify'])
    ];
  }

  /**
   * @deprecated
   * @see StringUtil::slugify()
   */
  public function slugify(string $string): string
  {
    return StringUtil::slugify($string);
  }
}
