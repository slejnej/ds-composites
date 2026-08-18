<?php

namespace Drupal\drupal_search\BaseRepository;

use Drupal;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Exception;

/**
 * Class BaseRepository
 *
 * @package Drupal\mantaray_base_repository\BaseRepository
 *
 * A base repository to extend, usage: `class NodeRepository extends
 *   BaseRepository {}` This will give you access to find, findOneBy and
 *   findBy. You can add as many repository methods as you wish
 *
 * Usage:
 * Get an instance of Node or null:
 *   \Drupal::service('<module>.repository.node')->find(1); Get an array of
 *   Nodes, or empty:
 *   \Drupal::service('<module>.repository.node')->findBy(['uid' => 15,
 *   'published' => true]);
 *
 */
abstract class BaseRepository {

  /**
   * @var string The machine name of the entity, e.g. 'node' or 'fcm_message'
   */
  protected string $entityType;

  /**
   * @var bool Whether to only return the records the user has access to
   */
  protected bool $accessCheck = TRUE;

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
    }
    catch (DatabaseExceptionWrapper $e) {
      return NULL;
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

      return $q->execute()->getResultCount();
    }
    catch (\Exception $e) {
      $this->logException($e);

      return 0;
    }
  }

  /**
   * @param array $criteria ['field' => 'value', 'field' => ['value' => 'xxx',
   *   'operator' => 'LIKE'], ...]
   * @param array $orderBy ['field' => 'DESC', 'field']
   * @param int|null $limit
   * @param int|null $offset
   *
   * @return array
   */
  public function findBy(array $criteria, array $orderBy = [], int $limit = NULL, int $offset = NULL): array
  {
    try {
      $q = $this->getBaseSelect();
      $this->addConditions($q, $criteria);
      $this->addOrderBy($q, $orderBy);

      if ($limit || $offset) {
        $q->range($offset ?? 0, $limit ?? PHP_INT_MAX);
      }

      return $this->fetchResults($q);
    }
    catch (\Exception $e) {
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

      return reset($result) ?: NULL;
    }
    catch (DatabaseExceptionWrapper $e) {
      return NULL;
    }
  }

  public function average(string $field, array $criteria = []): float
  {
    $values = $this->extractNumericFieldValues($field, $criteria);
    return count($values) ? array_sum($values) / count($values) : 0;
  }

  public function sum(string $field, array $criteria = []): float
  {
    $values = $this->extractNumericFieldValues($field, $criteria);
    return array_sum($values);
  }

  public function max(string $field, array $criteria = []): float
  {
    $values = $this->extractNumericFieldValues($field, $criteria);
    return count($values) ? max($values) : 0;
  }

  public function min(string $field, array $criteria = []): float
  {
    $values = $this->extractNumericFieldValues($field, $criteria);
    return count($values) ? min($values) : 0;
  }

  protected function extractNumericFieldValues(string $field, array $criteria = []): array
  {
    $entities = $this->findBy($criteria);
    $values = [];

    foreach ($entities as $entity) {
      if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
        $value = $entity->get($field)->value;
        if (is_numeric($value)) {
          $values[] = $value;
        }
      }
    }

    return $values;
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function getBaseSelect(): QueryInterface
  {
    /** @var IndexInterface $index */
    $index = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->load('default_data');

    if (!$index) {
      throw new \RuntimeException('Search API index "default_data" not found.');
    }

    return $index->query();
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function addConditions($q, array $conditions): void
  {
    foreach ($conditions as $field => $options) {
      $field = $this->getSearchApiFieldName($field);

      $operator = '=';
      $value = $options;

      if (is_array($options) && isset($options['value'])) {
        $value = $options['value'];
        $operator = $options['operator'] ?? '=';
      }

      if (in_array($value, ['IS NULL', 'IS NOT NULL'])) {
        $q->addCondition($field, NULL, $value);
      }
      else {
        $q->addCondition($field, $value, $operator);
      }
    }
  }

  /**
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function fetchResults(QueryInterface $query): array
  {
    $results = $query->execute();
    $entities = [];

    foreach ($results->getResultItems() as $item) {
      $entity = $item->getOriginalObject()->getValue();
      if ($entity instanceof EntityInterface) {
        $entities[] = $entity;
      }
    }

    return $entities;
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getStorage(): EntityStorageInterface
  {
    return Drupal::entityTypeManager()->getStorage($this->entityType);
  }

  protected function addOrderBy(QueryInterface $q, array $orderBy): void
  {
    foreach ($orderBy as $field => $direction) {
      //in case an associative array is passed, the key will be the index rather than the field and the direction will contain the field name
      if (!is_string($field)) {
        $q->sort($direction);
      }
      else {
        $q->sort($field, $direction);
      }
    }
  }

  private function logException(Exception $e): void
  {
    $message = sprintf('%s::%s - %s', $e->getFile(), $e->getLine(), $e->getMessage());

    Drupal::logger('base_repository')->error($message);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSearchApiFieldName(string $logicalField): string
  {
    static $fieldCache = [];

    if (isset($fieldCache[$logicalField])) {
      return $fieldCache[$logicalField];
    }

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->load('default_data');

    if (!$index) {
      throw new \RuntimeException('Search API index "default_data" not found.');
    }

    foreach ($index->getFields() as $field) {
      if ($field->getPropertyPath() === $logicalField || $field->getFieldIdentifier() === $logicalField) {
        return $fieldCache[$logicalField] = $field->getFieldIdentifier();
      }
    }

    // Fallback: assume logicalField is correct
    return $fieldCache[$logicalField] = $logicalField;
  }

}
