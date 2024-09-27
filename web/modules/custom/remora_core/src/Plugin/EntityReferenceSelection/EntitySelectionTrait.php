<?php

namespace Drupal\remora_core\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\remora_core\Exception\NotImplementedException;
use Drupal\remora_core\Service\ReferenceableEntity\ReferenceableEntityServiceInterface;

/**
 *  This plugin adds our own "Reference method" to reference fields.
 *  By default, all types of the field are referenceable, if a Paragraph/CT should NOT be referenceable,
 *  add an EventSubscriber and remove the entry using the delete method, see the README for a more extended explanation
 *
 * @see Drupal\remora_core\Event\ReferenceableEntityEvent
 */
trait EntitySelectionTrait
{


  protected ReferenceableEntityServiceInterface $referenceableEntityService;

  /**
   * Overrides the target_bundles with our own definition of entities
   *
   * {@inheritdoc}
   */
  public function getConfiguration(): array
  {
    $config = $this->configuration;
    $config['target_bundles'] = $this->referenceableEntities();

    return $config;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array
  {
    $supportedTypes = $this->referenceableEntityService->getAllReferenceableEntityTypes();

    $form['target_bundles']['#type'] = 'item';
    $form['target_bundles']['#options'] = [];
    $form['target_bundles']['#value'] = [];
    $form['target_bundles']['#disabled'] = true;
    $form['target_bundles']['#required'] = false;

    $desc = 'This setting is disabled. All entity references are handled through remora_core. The following types are enabled:<ul>';
    $desc .= implode('', array_map(fn(string $x) => "<li>$x</li>", $supportedTypes));
    $form['target_bundles']['#description'] = $desc . '</ul>';

    return $form;
  }

  /**
   * Gets the referenceable entities
   *
   * @return array The referenceable entities as per interface
   * @throws NotImplementedException If referneceableEntityService has not been set
   * @see ReferenceableEntityServiceInterface::getAllReferenceableEntityTypes()
   * @see self::$referenceableEntityService
   */
  private function referenceableEntities(): array
  {
    if(!isset($this->referenceableEntityService)) {
      throw new NotImplementedException('Referenceable Entity Service has not been implemented');
    }

    return $this->referenceableEntityService->getAllReferenceableEntityTypes();
  }
}
