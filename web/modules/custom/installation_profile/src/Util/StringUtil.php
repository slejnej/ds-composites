<?php

namespace Drupal\installation_profile\Util;

class StringUtil
{

  /**
   * Sanitizes a theme's human-readable name into a machine name
   * hyphens (-) and spaces ( ) are converted into underscores
   * All non-alphanumeric characters are removed
   *
   * @param string $humanReadableName The human-readable theme name
   * @return string The machine theme name
   */
  public static function sanitizeThemeName(string $humanReadableName): string
  {
    $machineName = preg_replace(
      ['/([- ])/', '/[^a-zA-Z0-9_]/'],
      ['_', ''],
      $humanReadableName
    );

    return strtolower($machineName);
  }

  /**
   * Finds the overlap between two texts and returns the overlapping segment
   * Works much like strspn, however returns the substr instead of the string length that overlaps
   * Order is irrelevant
   *
   * @param string $a The string to check
   * @param string $b The string to check against $a
   * @return string
   */
  public static function getOverlap(string $a, string $b): string
  {
    foreach(str_split($a) as $index => $char) {
      if(!isset($b[$index]) || $b[$index] !== $char) {
        break;
      }
    }

    return substr($a, 0, $index ?? 0);
  }

  /**
   * Creates a relative path from $a's location to $b
   * This is useful for linking files from one location to another
   * E.g. if $from = '/app/web/modules/contrib' and $to = '/app/web/themes/custom'
   * Result will be '../../themes/custom'
   *
   * If $from = '/app/web/themes/custom/barrio_base_theme/assets' and $to = '/app/web/modules/custom/test/scss/style.scss'
   * Result will be '../../../../modules/custom/test/scss/style.scss'
   *
   * @param string $from The path to create a relative path from
   * @param string $to The path to create a relative path to
   * @return string
   * @fixme if the from is a file, one too many ../ will be appended, tho this can be circumvented by using basename before calling this method
   */
  public static function createRelativePath(string $from, string $to): string
  {
    $overlappingPath = static::getOverlap($from, $to);

    // make sure the path ends on a slash, so the c in 'contrib' and 'custom' doesn't mess up the algo
    $overlappingPath = preg_replace('/[^\/]+$/', '', $overlappingPath);
    $overlapIndex = strlen($overlappingPath);

    // The path with the overlapping absolute path stripped
    $nonOverlapFrom = substr($from, $overlapIndex);
    $nonOverlapInTo = substr($to, $overlapIndex);

    // replace whatever directories are left with ../
    $relativeToPath = str_repeat('../', count(explode('/', $nonOverlapFrom)));


    return $relativeToPath . $nonOverlapInTo;
  }

}
