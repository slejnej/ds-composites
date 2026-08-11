<?php

namespace Drupal\drupal_search\Service;

use Drupal\Core\Database\Connection;
use Drupal\node\NodeInterface;

/**
 * Service to handle SEO exclusions.
 */
class SeoExclusionService
{

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs a new SeoExclusionService.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database)
  {
    $this->database = $database;
  }

  /**
   * Check if a node is excluded from search.
   *
   * @param NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   TRUE if the node is excluded, FALSE otherwise.
   * @throws \Exception
   */
  public function isNodeExcluded(NodeInterface $node): bool
  {
    $schemaHandler = $this->database->schema();
    if (!$schemaHandler->tableExists('drupal_search_exclude_from_index')) {
      return false;
    }

    return (bool)$this->database
      ->select('drupal_search_exclude_from_index', 'e')
      ->fields('e', ['nid'])
      ->condition('nid', $node->id())
      ->execute()
      ->fetchField();
  }

  public function getExcludedNids(): array
  {
    return $this->database
      ->select('drupal_search_exclude_from_index', 'e')
      ->fields('e', ['nid'])
      ->execute()
      ->fetchCol();
  }
}
