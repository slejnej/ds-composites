<?php

namespace Drupal\remora_core\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\remora_core\Enum\EventName;

/**
 * {@inheritdoc}
 *
 * @EntityReferenceSelection(
 *   id = "remora_referenceable_paragraph_types_sidebar",
 *   label = @Translation("Remora Referenceable Paragraph Types (Sidebar)"),
 *   group = "remora_referenceable_paragraph_types_sidebar",
 *   entity_types = {"paragraph"},
 *   weight = 0
 * )
 */
class SidebarParagraphSelection extends BaseParagraphSelection
{

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);

    $this->referenceableEntityService->setEventName(EventName::REFERENCEABLE_PARAGRAPH_TYPE_SIDEBAR_INDEX);
  }

}
