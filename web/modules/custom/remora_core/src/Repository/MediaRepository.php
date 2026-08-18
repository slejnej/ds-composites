<?php

namespace Drupal\remora_core\Repository;

use Drupal\drupal_search\BaseRepository\BaseRepository;
use Drupal\media\Entity\Media;

class MediaRepository extends BaseRepository
{
  protected string $entityType = 'media';

  public function __construct(private readonly FileRepository $fileRepository )
  {
    parent::__construct();
  }

  public function findByUri(string $uri): ?Media
  {
    $file = $this->fileRepository->findByUri($uri);
    if(!isset($file)) {
      return null;
    }

    return $this->findOneBy([
      'field_media_image' => $uri
    ]);
  }
}
