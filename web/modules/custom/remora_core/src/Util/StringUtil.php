<?php

namespace Drupal\remora_core\Util;

class StringUtil
{

  /**
   * Slugifies the given string (replaces all non-alphanumeric characters with dashes)
   *
   * @param string $string The string to slugify
   *
   * @return string Slugified string
   */
  public static function slugify(string $string): string
  {
    return strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $string));
  }

}
