<?php

namespace MantaRayMedia\BaseRepository\SearchAPI;

use Drupal;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Entity\Index;
use Exception;
use InvalidArgumentException;

/**
 * Class BaseRepository
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
  protected string $index = 'default_data';

  /**
   * @var Connection
   */
  protected readonly ?Index $searchIndex;

  public function __construct()
  {
    if(!Drupal::moduleHandler()->moduleExists('remora_search')) {
      return;
    }

    $storage = Drupal::entityTypeManager()->getStorage('search_api_index');
    $this->searchIndex = $storage?->load($this->index);
  }

  /**
   * Whether the search index is available for searching
   *
   * @return bool
   */
  public function isAvailable(): bool
  {
    if (! $this->searchIndex) {
      return false;
    }
    return ($this->searchIndex?->getServerInstance()->isAvailable() && $this->searchIndex?->status()) === true;
  }

  /**
   * Find an indexed entity by ID
   *
   * @param string $id
   * @return object|array|null
   */
  public function find(string $id): ?object
  {
    return $this->findOneBy([
      'nid' => $id
    ]);
  }

  /**
   * Find all indexed entities
   *
   * @param array $orderBy
   * @return array
   */
  public function findAll(array $orderBy = []): array
  {
    return $this->findBy([], $orderBy);
  }

  /**
   * Find indexed entities by specific field criteria
   *
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
      $this->addOptions($q, [
        'limit' => $limit,
        'offset' => $offset
      ]);

      $this->addConditions($q, $criteria);
      $this->addOrderBy($q, $orderBy);

      return $this->fetchResults($q);
    } catch(Drupal\Core\Database\DatabaseExceptionWrapper $e) {
      $this->logException($e);
      return [];
    }
  }

  /**
   * Find a single indexed entity by specific field criteria
   */
  public function findOneBy(array $criteria, array $orderBy = []): ?object
  {
    try {
      $result = $this->findBy($criteria, $orderBy,  1);

      return reset($result) ?: null;
    } catch(DatabaseExceptionWrapper $e) {
      return null;
    }
  }

  /**
   * Add mulitple conditions in an easy way
   * Formatted as:
   * [ 'field_name' => 'value' ]
   * [ 'field_name' => ['value' => 'xxx', 'operator' => 'LIKE'] ]
   *
   * @param QueryInterface $q
   * @param array $conditions
   * @return void
   */
  protected function addConditions(QueryInterface $q, array $conditions): void
  {
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

      $q->addCondition($field, $value, $operator);
    }
  }

  /**
   * Fetches the results and returns the original object
   *
   * @param QueryInterface $query
   * @return Drupal\Core\Entity\EntityInterface[]
   * @throws Drupal\search_api\SearchApiException
   */
  protected function fetchResults(QueryInterface $query): array
  {
    $result = $query->execute()->getResultItems();
    return array_map(function(Item $item) {
      try {
        return $item->getOriginalObject()->getValue();
      } catch(Drupal\search_api\SearchApiException $e) {
        $this->logException($e);
        return null;
      }
    }, $result);
  }

  /**
   * Add one or multiple options to the query
   *
   * @see Drupal\search_api\Query\Query::setOption() for all possible options
   * @param QueryInterface $query
   * @param array $options
   * @return void
   */
  protected function addOptions(QueryInterface $query, array $options): void
  {
    foreach($options as $key => $option) {
      if(isset($option)) {
        $query->setOption($key, $option);
      }
    }
  }

  /**
   * Creates a new query
   *
   * @return QueryInterface
   * @throws Drupal\search_api\SearchApiException
   */
  protected function getBaseSelect(): QueryInterface
  {
    return $this->searchIndex->query();
  }

  /**
   * Log a non-fatal exception to Drupal
   *
   * @param Exception $e
   * @return void
   */
  private function logException(Exception $e): void
  {
    $message = sprintf('%s::%s - %s', $e->getFile(), $e->getLine(), $e->getMessage());

    Drupal::logger('base_repository')->error($message);
  }

  /**
   * Add order by to the query
   *
   * @param QueryInterface $q
   * @param array $orderBy
   * @return void
   */
  protected function addOrderBy(QueryInterface $q, array $orderBy): void
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

}
