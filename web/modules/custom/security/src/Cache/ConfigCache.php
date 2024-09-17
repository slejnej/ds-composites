<?php

namespace Drupal\security\Cache;

use MantaRayMedia\CacheService\CacheService;

class ConfigCache extends CacheService
{
  protected static array $tags = ['config'];
}
