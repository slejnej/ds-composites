<?php

namespace Drupal\installation_profile\Util;

use JetBrains\PhpStorm\ArrayShape;

abstract class ArrayUtil
{

  /**
   * Flattens an array and returns the arrays distinct keys and values in two separate sub-arrays
   *
   * @param array $array The array to flatten
   * @return array[]
   */
  #[ArrayShape(['values' => 'mixed[]', 'keys' => 'scalar[]'])]
  public static function flattenKeysValues(array $array): array
  {
    $return = ['values' => [], 'keys' => []];
    array_walk_recursive($array, function(mixed $a, string $key) use (&$return) { $return['values'][] = $a; $return['keys'][] = $key; });
    return $return;
  }

}
