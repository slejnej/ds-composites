<?php

namespace Drupal\remora_core\Repository\SearchAPI;

use Drupal\search_api\Query\QueryInterface;
use MantaRayMedia\BaseRepository\SearchAPI\BaseRepository;

/**
 * Returns the SOLR nodes rather than the Drupal nodes
 */
class SolrNodeRepository extends BaseRepository
{

  protected function fetchResults(QueryInterface $query): array
  {
    return $query->execute()->getResultItems();
  }

}
