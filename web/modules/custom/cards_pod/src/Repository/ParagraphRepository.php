<?php

namespace Drupal\cards_pod\Repository;

use Drupal\drupal_search\BaseRepository\BundledRepository;

class ParagraphRepository extends BundledRepository
{
  protected string $entityType = 'paragraph';
  protected string $bundle = 'card_pea';
  protected string $bundleKey = 'type';

}
