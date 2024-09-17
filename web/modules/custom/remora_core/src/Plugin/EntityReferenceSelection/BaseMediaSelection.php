<?php

namespace Drupal\remora_core\Plugin\EntityReferenceSelection;

use Drupal;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\Plugin\EntityReferenceSelection\MediaSelection;
use Drupal\remora_core\Service\ReferenceableEntity\ReferenceableEntityServiceInterface;


class BaseMediaSelection extends MediaSelection implements ReferenceableEntitySelectionInterface
{
  use EntitySelectionTrait {
    EntitySelectionTrait::buildConfigurationForm as buildConfigurationFormTrait;
    EntitySelectionTrait::getConfiguration as getConfigurationTrait;
  }

  protected ReferenceableEntityServiceInterface $referenceableEntityService;


  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);

    $this->referenceableEntityService = Drupal::service('remora_core.service.referenceable_media_type');
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
