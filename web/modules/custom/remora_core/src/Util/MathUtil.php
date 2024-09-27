<?php

namespace Drupal\remora_core\Util;

class MathUtil
{

  /**
   * Clamps a value between a lower and upper bound. Examples:
   * MathUtil::clamp(5, 0, 10) => 5
   * MathUtil::clamp(-5, 0, 10) => 0
   * MathUtil::clamp(15, 0, 10) => 10
   *
   * @param int|float $val The value to clamp
   * @param int|float $min The lower bound
   * @param int|float $max The upper bound
   * @return int|float The clamped value
   */
  public static function clamp(int|float $val, int|float $min, int|float $max): int|float
  {
    return max($min, min($max, $val));
  }

}
