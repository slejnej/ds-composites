<?php

namespace MantaRayMedia\BaseRepository;

use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Used as a base for entities that have bundles such as taxonomy terms and nodes
 */
abstract class BundledRepository extends BaseRepository
{

  protected string $bundle;
  /** @var string The key to use when filtering the bundle. For taxonomy terms its vid, for nodes its type */
  protected string $bundleKey;

  protected function getBaseSelect(): QueryInterface
  {
    $stmt = parent::getBaseSelect();

    if(isset($this->bundle, $this->bundleKey)) {
      $this->addConditions($stmt, [$this->bundleKey => $this->bundle]);
    }

    return $stmt;
  }

  protected function getAggregateBaseSelect(): QueryAggregateInterface
  {

    $stmt = parent::getAggregateBaseSelect();

    if(isset($this->bundle, $this->bundleKey)) {
      $this->addConditions($stmt, [$this->bundleKey => $this->bundle]);
    }

    return $stmt;
  }

  /**
   * Returns entities with the given content type. Ignores the class' bundle property
   *
   * @param string $type The type to filter for
   * @param array $orderBy
   * @param int|null $limit
   * @param int|null $offset
   * @return array
   */
  public function findByType(string $type, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
  {
    return $this->findBy([
      $this->bundleKey => $type
    ], $orderBy, $limit, $offset);
  }


}
