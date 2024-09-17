<?php

namespace MantaRayMedia\BaseRepository;

use Drupal;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Exception;
use InvalidArgumentException;

/**
 * Class BaseRepository
 * @package Drupal\mantaray_base_repository\BaseRepository
 *
 * A base repository to extend, usage: `class NodeRepository extends BaseRepository {}`
 * This will give you access to find, findOneBy and findBy. You can add as many repository methods as you wish
 *
 * Usage:
 * Get an instance of Node or null: \Drupal::service('<module>.repository.node')->find(1);
 * Get an array of Nodes, or empty: \Drupal::service('<module>.repository.node')->findBy(['uid' => 15, 'published' => true]);
 *
 */
abstract class BaseRepository
{
  /**
   * @var string The machine name of the entity, e.g. 'node' or 'fcm_message'
   */
  protected string $entityType;

  /**
   * @var bool Whether to only return the records the user has access to
   */
  protected bool $accessCheck = true;

  /**
   * @var Connection
   */
  private Connection $connection;

  public function __construct()
  {
    $this->connection = Drupal::database();
  }

  public function find(int $id): ?object
  {
    try {
      return $this->getStorage()->load($id);
    } catch(DatabaseExceptionWrapper $e) {
      return null;
    }
  }

  public function findAll(array $orderBy = []): array
  {
    return $this->findBy([], $orderBy);
  }

  public function count(array $criteria): int
  {
    try {
      $q = $this->getBaseSelect();
      $this->addConditions($q, $criteria);

      return $q->count()->execute();
    } catch(DatabaseExceptionWrapper $e) {
      $this->logException($e);

      return 0;
    }
  }

  /**
   * @param array $criteria ['field' => 'value', 'field' => ['value' => 'xxx', 'operator' => 'LIKE'], ...]
   * @param array $orderBy ['field' => 'DESC', 'field']
   * @param int|null $limit
   * @param int|null $offset
   * @return array
   */
  public function findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null): array
  {
    try {
      $q = $this->getBaseSelect();
      $this->addConditions($q, $criteria);
      $this->addOrderBy($q, $orderBy);

      if($limit || $offset) {
        $q->range($offset ?? 0, $limit ?? PHP_INT_MAX);
      }

      return $this->fetchResults($q);
    } catch(Drupal\Core\Database\DatabaseExceptionWrapper $e) {
      $this->logException($e);
      return [];
    }
  }

  /**
   * @see BaseRepository::findBy()
   */
  public function findOneBy(array $criteria, array $orderBy = []): ?object
  {
    try {
      $result = $this->findBy($criteria, $orderBy, 1);

      return reset($result) ?: null;
    } catch(DatabaseExceptionWrapper $e) {
      return null;
    }
  }

  public function average(string $field, array $criteria = []): int
  {
    return $this->aggregate('AVG', $field, $criteria)[0][$field] ?? 0;
  }

  public function min(string $field, array $criteria = []): int
  {
    return $this->aggregate('MIN', $field, $criteria)[0][$field] ?? 0;
  }

  public function max(string $field, array $criteria = []): int
  {
    return $this->aggregate('MAX', $field, $criteria)[0][$field] ?? 0;
  }

  public function sum(string $field, array $criteria = []): int
  {
    return $this->aggregate('SUM', $field, $criteria)[0][$field] ?? 0;
  }

  protected function getBaseSelect(): QueryInterface
  {
    return Drupal::entityQuery($this->entityType)->accessCheck($this->accessCheck);
  }

  protected function getAggregateBaseSelect(): QueryAggregateInterface
  {
    return Drupal::entityQueryAggregate($this->entityType)->accessCheck($this->accessCheck);
  }

  /**
   * Executes an aggregate function
   *
   * @param string $aggregateFunction The aggregate function, e.g. COUNT, MAX, SUM
   * @param string $field
   * @param array $criteria
   * @return array
   */
  protected function aggregate(string $aggregateFunction, string $field, array $criteria): array
  {
    $q = $this->getAggregateBaseSelect();
    $q->aggregate($field, $aggregateFunction, null, $field);
    $this->addConditions($q, $criteria);

    return $q->execute();
  }

  protected function addConditions($q, array $conditions): void
  {
    if(!method_exists($q, 'condition')) {
      throw new InvalidArgumentException('Unsupported class ' . get_class($q) . '! Class must implement method "condition"');
    }

    foreach($conditions as $field => $options) {
      $operator = '=';

      if(is_array($options) && isset($options['operator'], $options['value'])) {
        $operator = $options['operator'];
        $value = $options['value'];
      } else {
        $value = $options;
      }

      if(in_array($value, ['IS NULL', 'IS NOT NULL'])) {
        $operator = $value;
        $value = null;
      } else if($value instanceof ContentEntityInterface) {
        $value = $value->id();
      }

      $q->condition($field, $value, $operator);
    }
  }

  protected function fetchResults(QueryInterface $query): array
  {
    return $this->getStorage()->loadMultiple($query->execute());
  }

  protected function getStorage(): EntityStorageInterface
  {
    return Drupal::entityTypeManager()->getStorage($this->entityType);
  }

  protected function addOrderBy(QueryInterface $q, array $orderBy)
  {
    foreach($orderBy as $field => $direction) {

      //in case an associative array is passed, the key will be the index rather than the field and the direction will contain the field name
      if(!is_string($field)) {
        $q->sort($direction);
      } else {
        $q->sort($field, $direction);
      }
    }
  }

  private function logException(Exception $e): void
  {
    $message = sprintf('%s::%s - %s', $e->getFile(), $e->getLine(), $e->getMessage());

    Drupal::logger('base_repository')->error($message);
  }

}
