<?php

namespace Drupal\remora_core\Twig\Extension;

use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
class ArrayExtension extends AbstractExtension
{

  public function getFilters(): array
  {
    return [
      new TwigFilter('merge_safe', [$this, 'mergeSafe']),
    ];
  }

  /**
   * Merges the given arrays into a single array.
   * Before merging, it removes any null values with an empty array.
   * Other type checking handled by twig_array_merge().
   *
   * @param iterable|null ...$arrays The arrays to merge into the first array
   * @return array The merged array
   * @throws RuntimeError
   * @see twig_array_merge()
   *
   */
  public function mergeSafe(?iterable ...$arrays): array
  {
    $arrays = array_map(fn(?iterable $array) => $array ?? [], $arrays);
    return twig_array_merge(...$arrays);
  }

}
