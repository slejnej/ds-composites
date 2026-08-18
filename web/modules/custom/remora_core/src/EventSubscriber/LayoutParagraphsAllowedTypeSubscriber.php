<?php

namespace Drupal\remora_core\EventSubscriber;

use Drupal;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Url;
use Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent;
use Drupal\remora_core\Plugin\EntityReferenceSelection\BaseParagraphSelection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes all paragraph types from the layout builder that aren't allowed
 * This is a workaround for the fact that the layout builder by default uses allowed_types
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class LayoutParagraphsAllowedTypeSubscriber implements EventSubscriberInterface
{

  public function __construct(private readonly SelectionPluginManagerInterface $selectionPluginManager)
  {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array
  {
    if(!class_exists(LayoutParagraphsAllowedTypesEvent::class)) {
      return [];
    }
    
    return [
      // Static class constant => method on this class.
      LayoutParagraphsAllowedTypesEvent::EVENT_NAME => 'onList'
    ];
  }

  /**
   * Limits the allowed entities to those set on the field
   * By default the field checks the allowed_types, but we do not add this list so our product can be modular
   *
   * @param LayoutParagraphsAllowedTypesEvent $event
   * @return void
   */
  public function onList(LayoutParagraphsAllowedTypesEvent $event): void
  {
    $handler = $event->getLayout()->getParagraphsReferenceField()->getFieldDefinition();
    if(!isset($handler)) {
      return;
    }

    $handler = $this->selectionPluginManager->getSelectionHandler($handler);
    if(!$handler instanceof BaseParagraphSelection) {
      return;
    }


    // get all the types the layout builder thinks are allowed
    $types = $event->getTypes();

    // get all the types we think are allowed
    $allowedTypes = $handler->getReferenceableEntityService()->getAllReferenceableEntityTypes();

    // only keep the types we agree are allowed
    $types = array_intersect_key($types, $allowedTypes);
    $event->setTypes($types);
  }
}
