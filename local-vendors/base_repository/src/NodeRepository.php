<?php

namespace MantaRayMedia\BaseRepository;

use Drupal\node\Entity\Node;

/**
 * Class NodeRepository
 * @package Drupal\mantaray_base_repository\NodeRepository
 *
 * @method Node|null find(int $id)
 * @method Node[] findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null)
 * @method Node|null findOneBy(array $criteria, array $orderBy = [])
 * @method Node[] findAll(array $orderBy = [])
 * @method Node[] findByType(string $type, array $orderBy = [], int $limit = null, int $offset = null)
 */
class NodeRepository extends BundledRepository
{

  protected string $entityType = 'node';
  protected string $bundleKey = 'type';

}
