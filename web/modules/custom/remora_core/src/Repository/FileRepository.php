<?php

namespace Drupal\remora_core\Repository;

use Drupal\file\Entity\File;
use MantaRayMedia\BaseRepository\BaseRepository;

class FileRepository extends BaseRepository
{
  protected string $entityType = 'file';

  public function findByUri(string $uri): ?File
  {
    return $this->findOneBy(['uri' => $uri]);
  }

}
