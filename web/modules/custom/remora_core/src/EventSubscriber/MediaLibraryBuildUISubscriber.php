<?php

namespace Drupal\remora_core\EventSubscriber;

use Drupal;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;
use Drupal\media_library\Event\BuildUIEvent;
use Drupal\remora_core\Plugin\EntityReferenceSelection\BaseMediaSelection;
use Drupal\remora_core\Plugin\EntityReferenceSelection\BaseParagraphSelection;
use Drupal\remora_core\Plugin\EntityReferenceSelection\ReferenceableEntitySelectionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes all paragraph types from the layout builder that aren't allowed
 * This is a workaround for the fact that the layout builder by default uses allowed_types
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class MediaLibraryBuildUISubscriber implements EventSubscriberInterface
{

  public function __construct(private readonly SelectionPluginManagerInterface $selectionPluginManager)
  {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array
  {
    if(!class_exists(BuildUIEvent::class)) {
      return [];
    }

    return [
      // Static class constant => method on this class.
      BuildUIEvent::EVENT_NAME => 'onBuild'
    ];
  }

  /**
   * Limits the allowed entities to those set on the media field
   * By default the field checks the allowed_types, but we do not add this list so our product can be modular
   *
   * @param BuildUIEvent $event
   * @return void
   */
  public function onBuild(BuildUIEvent $event): void
  {
    // get the node type and field the media library is opened from
    $entityFieldManager = Drupal::service('entity_field.manager');
    $params = $event->mediaLibraryState->getOpenerParameters();

    // If not being added on a node via a field i.e. wysiwyg revert to options from config
    if (!isset($params['entity_type_id'])) {
      return;
    }

    /** @var FieldConfig[] $fields */
    $fields = $entityFieldManager->getFieldDefinitions($params['entity_type_id'], $params['bundle']);
    $handler = $fields[$params['field_name']] ?? null;

    if(!isset($handler)) {
      return;
    }

    // get the selection handler
    $handler = $this->selectionPluginManager->getSelectionHandler($handler);
    if(!$handler instanceof ReferenceableEntitySelectionInterface) {
      return;
    }


    // get all the types the layout builder thinks are allowed
    $types = $event->mediaLibraryState->getAllowedTypeIds();

    // get all the types we think are allowed
    $allowedTypes = $handler->getReferenceableEntityService()->getAllReferenceableEntityTypes();

    // only keep the types we agree are allowed
    $types = array_intersect_key($types, $allowedTypes);
    $event->setAllowedTypeIDs($types);
  }
}
