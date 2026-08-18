<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\remora_core\Twig\Parser\RemoraIncludeNodeParser;
use Twig\Extension\AbstractExtension;

/**
 * Twig extension.
 */
class RemoraIncludeNode extends AbstractExtension
{

  public function getTokenParsers(): array
  {
    return [
      // Adds {% remora_include %}
      new RemoraIncludeNodeParser(),
    ];
  }

}
