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
      'slugify' => new TwigFilter('slugify', StringUtil::slugify(...)),
      'snakecase' => new TwigFilter('snakecase', StringUtil::snakeCaseify(...)),
      'snakecaseify' => new TwigFilter('snakecaseify', StringUtil::snakeCaseify(...)),
    ];
  }
}
