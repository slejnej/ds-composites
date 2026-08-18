<?php

namespace Drupal\remora_core\Plugin\EntityReferenceSelection;

use Drupal;
use Drupal\Core\Entity\Annotation\EntityReferenceSelection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\remora_core\Service\ReferenceableEntity\ReferenceableEntityServiceInterface;


/**
 * This plugin adds our own "Reference method" to fields.
 * By default, all content types are referenceable, if a CT should NOT be referenceable,
 * add an EventSubscriber and remove the CT, see the README for a more extended explanation
 *
 * @EntityReferenceSelection(
 *   id = "remora_referenceable_content_types",
 *   label = @Translation("Remora Referenceable Content Types"),
 *   group = "remora_referenceable_content_types",
 *   entity_types = {"node"},
 *   weight = 0
 * )
 */
class RemoraNodeSelection extends DefaultSelection implements ReferenceableEntitySelectionInterface
{
  use EntitySelectionTrait {
    EntitySelectionTrait::buildConfigurationForm as buildConfigurationFormTrait;
    EntitySelectionTrait::getConfiguration as getConfigurationTrait;
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);

    $this->referenceableEntityService = Drupal::service('remora_core.service.referenceable_content_type');
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
  {
    return $this->buildConfigurationFormTrait($form, $form_state);
  }

  public function getConfiguration(): array
  {
    return $this->getConfigurationTrait();
  }

  public function getReferenceableEntityService(): ReferenceableEntityServiceInterface
  {
    return $this->referenceableEntityService;
  }
}
