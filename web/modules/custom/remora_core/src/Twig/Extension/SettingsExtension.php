<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal;
use Drupal\Core\Site\Settings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
class SettingsExtension extends AbstractExtension {

  public function getFunctions(): array
  {
    return [
      'drupal_setting' => new TwigFunction('drupal_setting', Settings::get(...)),
      'drupal_state' => new TwigFunction('drupal_state', Drupal::state()->get(...)),
    ];
  }
}
