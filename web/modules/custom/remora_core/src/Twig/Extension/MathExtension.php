<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\Core\Session\AccountInterface;
use Drupal\remora_core\Util\MathUtil;
use Drupal\user\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

/**
 * Twig extension.
 */
class MathExtension extends AbstractExtension {

  public function getFilters(): array
  {
    return [
      'clamp' => new TwigFilter('clamp', [MathUtil::class, 'clamp'])
    ];
  }
}
