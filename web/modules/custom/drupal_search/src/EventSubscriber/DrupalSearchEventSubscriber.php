<?php

namespace Drupal\drupal_search\EventSubscriber;

use Drupal\drupal_search\Service\ContentTypeCacheService;
use Drupal\node\Entity\Node;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\IndexInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DrupalSearchEventSubscriber.
 */
class DrupalSearchEventSubscriber implements EventSubscriberInterface
{

  /**
   * Reacts to the indexing items event.
   *
   * @param \Drupal\search_api\Event\IndexingItemsEvent $event
   *   The indexing items event.
   */
  public function onIndexingItems(IndexingItemsEvent $event): void
  {
    $index = $event->getIndex();

    if ($index->id() !== 'default_data') {
      return;
    }

    $service = \Drupal::service('drupal_search.seo_exclusion_service');
    $excludedNids = $service->getExcludedNids();

    if (!empty($excludedNids)) {
      $items = $event->getItems();

      foreach ($items as $id => $item) {
        $entity = $item->getOriginalObject()->getValue();

        if ($entity instanceof Node && in_array($entity->id(), $excludedNids, true)) {
          unset($items[$id]);
        }
      }

      $event->setItems($items);
    }

    $this->updateRenderedItemViewMode($event->getIndex());
  }

  /**
   * For a reason only known to whatever you believe in, the rendered item view mode isn't being updated when a query is executed,
   * so we have to do it manually here as well as when indexing items.
   *
   * @param QueryPreExecuteEvent $event
   * @return void
   */
  public function onQueryPreExecute(QueryPreExecuteEvent $event): void
  {
    $query = $event->getQuery();
    $index = $query->getIndex();

    if ($index->id() !== 'default_data') {
      return;
    }

    $this->updateRenderedItemViewMode($index);
  }

  /**
   * Updates the rendered item view mode for the default_data index.
   *
   * @param Index $index
   * @return void
   */
  private function updateRenderedItemViewMode(IndexInterface $index): void
  {
    $fields = $index->getFields();
    $configuration = $fields['rendered_item']->getConfiguration();

    $currentConfig = md5(var_export($configuration, true));

    // if the index's rendered item view mode is already set to the expected config, do nothing
    if(ContentTypeCacheService::read('drupal_search.rendered_item.hash') === $currentConfig) {
      return;
    }

    // try to load the configuration we want from cache
    $searchRenderedViewMode = ContentTypeCacheService::get('drupal_search.rendered_item.config', fn() => $this->generateRenderedItemConfig());
    $configuration['view_mode']['entity:node'] = $searchRenderedViewMode;

    // update the MD5 hash in cache
    ContentTypeCacheService::set('drupal_search.rendered_item.hash', md5(var_export($configuration, true)));

    // update the rendered item configuration
    if (!isset($fields['rendered_item'])) {
      return;
    }

    $fields['rendered_item']->setConfiguration($configuration);
    $index->setFields($fields);
  }

  /**
   * Generates a config array for the rendered item field for each CT
   *
   * @return array An array containing the view mode for each CT, indexed by CT machine name
   */
  private function generateRenderedItemConfig(): array
  {
    $entityManager = \Drupal::service('entity_type.manager');
    $contentTypes = ContentTypeCacheService::getCachedContentTypes();

    $searchRenderedViewMode = [];
    foreach ($contentTypes as $type) {
      $display = $entityManager->getStorage('entity_view_display')
                               ->load('node.' . $type . '.search_rendered_output');

      $searchRenderedViewMode[$type] = $display ? 'search_rendered_output' : 'default';
    }

    return $searchRenderedViewMode;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array
  {
    return [
      SearchApiEvents::INDEXING_ITEMS => ['onIndexingItems'],
      SearchApiEvents::QUERY_PRE_EXECUTE => ['onQueryPreExecute'],
    ];
  }

}
