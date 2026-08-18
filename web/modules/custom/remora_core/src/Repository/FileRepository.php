<?php

namespace Drupal\remora_core\Repository;

use Drupal\drupal_search\BaseRepository\BaseRepository;
use Drupal\file\Entity\File;

class FileRepository extends BaseRepository
{
  protected string $entityType = 'file';

  public function findByUri(string $uri): ?File
  {
    $files = $this->getStorage()->loadByProperties(['uri' => $uri]);

    if (count($files) > 1) {
      \Drupal::logger('remora_core')->warning(
        'Multiple files found for URI "@uri". Expected only one.',
        ['@uri' => $uri]
      );
      return null;
    }

    $file = reset($files);

    return $file instanceof File ? $file : null;
  }
}
