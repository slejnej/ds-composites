<?php

namespace Drupal\drupal_search\Service;

use Drupal\node\Entity\NodeType;
use MantaRayMedia\CacheService\CacheService;

class ContentTypeCacheService extends CacheService
{
  const CID = 'drupal_search_content_type_cache:list';

  protected static array $tags = ['config:node_type_list'];

  /**
   * @return string[] A string array of CT machine names
   */
  public static function getCachedContentTypes(): array
  {
    return self::get(self::CID, fn(): array => self::getContentTypes());
  }

  /**
   * @return string[] A string array of CT machine names
   */
  private static function getContentTypes(): array
  {
    $cts = NodeType::loadMultiple();
    return array_map(fn(NodeType $ct): string => $ct->id(), $cts);
  }

  public static function invalidate(): void {
    self::delete(self::CID);
  }
}
