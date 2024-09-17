<?php

namespace MantaRayMedia\BaseRepository;

/**
 * Class UserRepository
 * @package Drupal\mantaray_base_repository
 *
 * @method \Drupal\user\Entity\User|null find(int $id)
 * @method \Drupal\user\Entity\User[] findBy(array $criteria, array $orderBy = [], int $limit = null, int $offset = null)
 * @method \Drupal\user\Entity\User|null findOneBy(array $criteria, array $orderBy = [])
 * @method \Drupal\user\Entity\User[] findAll(array $orderBy = [])
 */
class UserRepository extends BaseRepository
{

  protected string $entityType = 'user';

}
