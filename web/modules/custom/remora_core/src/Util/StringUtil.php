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

  /**
   * Removes illegal characters from filename
   *
   * @param string $filename
   * @return string
   */
  public static function sanitizeFilename(string $filename): string
  {
    return str_replace(['\\', '\'', '"', '<', '>', ':', '/', '|', '?', '*'],  '_', $filename);
  }

  /**
   * Converts all non-alphanumeric characters to underscores
   * Replaces multiple consecutive invalid characters with a single underscore
   * Makes sure to not end on an underscore
   *
   * @param string $text The text to snake_caseify
   * @return string The snake_caseified text, example: "Hello World" -> "hello_world", "Implementer (in-country)" -> "implementer_in_country
   */
  public static function snakeCaseify(string $text): string
  {
    return trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $text)), '_');
  }

}
