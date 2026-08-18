<?php

namespace Drupal\remora_core\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Twig extension.
 */
class TestsExtension extends AbstractExtension {

  public function getTests(): array
  {
    return [
      'numeric' => new TwigTest('numeric', 'is_numeric'),
      'array' => new TwigFilter('array', 'is_array'),
      'string' => new TwigFilter('string', 'is_string'),
    ];
  }
}
