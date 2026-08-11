<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\Component\Uuid\Php;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
class IDExtension extends AbstractExtension {

  public function __construct(private readonly Php $uuidGenerator)
  {
  }

  public function getFunctions(): array
  {
    return [
      'uuid' => new TwigFunction('uuid', [$this->uuidGenerator, 'generate']),
      'html_id' => new TwigFunction('html_id', [$this, 'randomID']),
    ];
  }

  /**
   * Generates a random valid HTML ID
   */
  public function randomID(): string
  {
    do {
      $result = $this->uuidGenerator->generate();
    } while(preg_match('/^[0-9]/', $result) === 1);

    return $result;
  }
}
