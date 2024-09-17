<?php

namespace MantaRayMedia\CacheService;

use Drupal;
use Drupal\Core\Cache\Cache;

/**
 * A wrapper for the drupal cache to make it easier to use
 * It is strongly recommended to extend this class, since tags are not supported without extending this class
 *
 * @class CacheService
 */
class CacheService
{
  /** @var string The bin to store the items to */
  protected static string $bin = 'default';
  /** @var array These tags will be applied to all the cached items. There is no setter for this on purpose */
  protected static array $tags = [];

  /**
   * @param string $key The cache ID
   * @param callable $callback A callback to generate the data if no data exists for the cache key, if not undefined and there was a cache miss NULL will be returned
   * @param int $ttl The TTL in seconds, defaults to PERMANENT
   * @returns mixed The data, or NULL if there was a cache miss and no callback method was defined
   */
  public static function get(string $key, callable $callback = null, int $ttl = Cache::PERMANENT)
  {
    $cachedData = Drupal::cache(static::$bin)->get($key);
    if(isset($cachedData->data)) {
      $data = $cachedData->data;
    } else if(isset($callback)) {
      $data = $callback();
      self::set($key, $data, $ttl);
    }

    return $data;
  }

  /**
   * Sets a value in the cache, overwriting any existing value
   *
   * @param string $key The cache ID
   * @param mixed $value The value to store
   * @param int $ttl The TTL in seconds
   * @return void
   */
  public static function set(string $key, mixed $value, int $ttl = Cache::PERMANENT): void
  {
    if($ttl !== Cache::PERMANENT) {
      $ttl = time() + $ttl;
    }

    Drupal::cache(static::$bin)->set($key, $value, $ttl, static::$tags);
  }

  /**
   * Returns true if the key exists in the cache
   *
   * @param string $key The cache ID
   * @return bool True if the key exists, false otherwise
   */
  public static function has(string $key): mixed
  {
    $cachedData = Drupal::cache(static::$bin)->get($key);
    return isset($cachedData->data);
  }

  /**
   * Reads a value but does not generate it if it does not exist
   *
   * @param string $key The cache ID
   * @return mixed The value or null if it does not exist
   */
  public static function read(string $key): mixed
  {
    $cachedData = Drupal::cache(static::$bin)->get($key);
    if(isset($cachedData->data)) {
      return $cachedData->data;
    }

    return null;
  }

  /**
   * Delete a value from cache
   *
   * @param string $key The cache ID to remove
   * @return void
   */
  public static function delete(string $key) {
    Drupal::cache(static::$bin)->delete($key);
  }

  /**
   * Clear all items from the cache with the tags set in the class
   *
   * @return void
   */
  public static function clear()
  {
    /** @var Drupal\Core\Cache\CacheTagsInvalidator $cacheInvalidator */
    $cacheInvalidator = Drupal::service('cache_tags.invalidator');
    $cacheInvalidator->invalidateTags(static::$tags);
  }
}
