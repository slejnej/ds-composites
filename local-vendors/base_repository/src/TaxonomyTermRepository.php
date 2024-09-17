<?php

namespace MantaRayMedia\BaseRepository;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class TaxonomyTermRepository
 * @package Drupal\mantaray_base_repository
 *
 * @method Term|null find(int $id)
 * @method Term[] findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null)
 * @method Term|null findOneBy(array $criteria, array $orderBy = [])
 * @method Term[] findAll(array $orderBy = [])
 * @method Term[] findByType(string $type, array $orderBy = [], int $limit = null, int $offset = null)
 */
class TaxonomyTermRepository extends BundledRepository
{
  protected string $entityType = 'taxonomy_term';
  protected string $bundleKey = 'vid';

}
