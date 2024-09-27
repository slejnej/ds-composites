<?php

namespace Drupal\remora_core\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
class FieldCheckExtension extends AbstractExtension
{

  public function getFunctions(): array
  {
    return [
      new TwigFunction('any_fields_not_empty', [$this, 'anyFieldsNotEmpty']),
    ];
  }

  public function getFilters(): array
  {
    return [
      new TwigFilter('has_any', [$this, 'hasAnyField']),
    ];
  }

  public function hasAnyfield(array $content, string ...$fields): bool
  {
    return $this->anyFieldsNotEmpty($content, $fields);
  }

  public function anyFieldsNotEmpty(array $content, array $fields): bool
  {
    foreach ($fields as $field) {
      if (isset($content[$field]['#items']) && !empty($content[$field]['#items'])) {
        return true;
      }
    }

    return false;
  }

}
