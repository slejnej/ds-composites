<?php

namespace Drupal\remora_core\Service;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\Field;
class AddNewField
{
  /**
   * TaggingConfigurationForm constructor.
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(private readonly EntityTypeManagerInterface $entityTypeManager)
  {
  }

  /**
   * @param string $field
   * @param array $filedSpecification
   * @param string $destinationMachineName
   * @param string $sourceType
   * This is the location of new field
   *
   * @param string $destinationType
   * This is what will be referenced in new field
   *
   * @return void
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   */
  public function createField(string $field, array $filedSpecification, string $destinationMachineName, string $sourceType, string $destinationType): void
  {
    $field_storage = FieldStorageConfig::loadByName($sourceType, $field);

    // Create field storage if it doesn't exist.
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create([
        'entity_type' => $sourceType,
        'field_name' => $field,
        'type' => 'entity_reference',
        'cardinality' => $filedSpecification['cardinality'],
        'settings' => [
          'target_type' => $destinationType,
        ],
      ]);
      $field_storage->save();
    }

    $field_config = FieldConfig::loadByName($sourceType, $destinationMachineName, $field);

    // check if field is already there
    if (!$field_config) {
      // Attach the field to the content type.
      $field_config = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $destinationMachineName,
        'label' => $filedSpecification['label'],
        'required' => FALSE,
        'description' => $filedSpecification['description'],
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [
              $filedSpecification['reference'] => $filedSpecification['reference'],
            ],
          ],
        ],
      ]);
      $field_config->save();

      if ($sourceType === 'node') {
        // Default
        $display = $this->entityTypeManager->getStorage('entity_view_display')
          ->load($sourceType . '.' . $destinationMachineName . '.default');
        $display->setComponent($field, [
          'type' => 'entity_reference_entity_view',
          'weight' => 10,
        ])->save();

        // Search display
        $display = $this->entityTypeManager->getStorage('entity_view_display')
          ->load($sourceType . '.' . $destinationMachineName . '.search_index');
        if ($display) {
          $display->setComponent($field, [
            'type' => 'entity_reference_label',
            'weight' => 10,
            'label' => 'hidden',
            'settings' => [
              'link' => false,
            ]
          ])->save();
        }

        // Search rendered output
        $display = $this->entityTypeManager->getStorage('entity_view_display')
          ->load($sourceType . '.' . $destinationMachineName . '.search_rendered_output');
        if ($display) {
          $display->setComponent($field, [
            'type' => 'entity_reference_label',
            'weight' => 10,
          ])->save();
        }
        // Card
        $display = $this->entityTypeManager->getStorage('entity_view_display')
          ->load($sourceType . '.' . $destinationMachineName . '.card');
        if ($display) {
          $display->setComponent($field, [
            'type' => 'entity_reference_label',
            'weight' => 10,
            'label' => 'hidden',
            'settings' => [
              'link' => false,
            ]
          ])->save();
        }
        // Content specific search index
        $display = $this->entityTypeManager->getStorage('entity_view_display')
          ->load($sourceType . '.' . $destinationMachineName . '.content_specific_search_index');
        if ($display) {
          $display->setComponent($field, [
            'type' => 'entity_reference_label',
            'weight' => 10,
            'label' => 'hidden',
            'settings' => [
              'link' => false,
            ]
          ])->save();
        }
        // Full content
        $display = $this->entityTypeManager->getStorage('entity_view_display')
          ->load($sourceType . '.' . $destinationMachineName . '.full');
        if ($display) {
          $display->setComponent($field, [
            'type' => 'entity_reference_label',
            'weight' => 10,
            'label' => 'hidden',
            'settings' => [
              'link' => false,
            ]
          ])->save();
        }
      }

      // Form display settings for the field.
      $form_display = $this->entityTypeManager->getStorage('entity_form_display')
        ->load($sourceType . '.' . $destinationMachineName . '.default');

      $form_display->setComponent($field, [
        'type' => $filedSpecification['form_widget'],
        'weight' => $filedSpecification['weight'],
        'region' => 'content'
      ]);

      if ($sourceType === 'node') {
        $field_group = $form_display->getThirdPartySetting('field_group', $filedSpecification['parent']);

        if (!$field_group) {
          $seoGroup = $form_display->getThirdPartySetting('field_group', 'group_summary_seo');
          if ($seoGroup) {
            $seoGroup['weight'] = 1000;
            $form_display->setThirdPartySetting('field_group', 'group_summary_seo', $seoGroup);
          }
          $field_group = [
            "children" => [$field],
            "label" => $filedSpecification['field_group_label'],
            "region" => "content",
            "parent_name" => 'group_page_tabs',
            "weight" => 999,
            "format_type" => $filedSpecification['field_group_type'],
            "format_settings" => [
              "classes" => "",
              "show_empty_fields" => true,
              "id" => "",
              "open" => false,
              "description" => $filedSpecification['field_group_description'],
              "required_fields" => false
            ]
          ];
        } else {
          $field_group["children"][] = $field;
        }

        $form_display->setThirdPartySetting('field_group', $filedSpecification['parent'], $field_group);
      }

      $form_display->save();
    }
  }

/**
 * allows generation of every type of field and adding it to correct displays, see example in map_nugget
 *
 * @param string $fieldName
 * @param array $fieldStorage
 * @param array $fieldConfiguration
 * @param string $sourceType // type of drupal entity on which field lies
 * @param string $destinationMachineName // machine name of entity
 * @param array $displays // array of displays with specifications per each
 * @param array $formDisplaySpecification // specification for form display
 * @param string $fieldGroup // name of parent fieldGroup where field should be added on form display
 */
  public function createFieldFromSpecification(string $fieldName, array $fieldStorage, array $fieldConfiguration, string $sourceType, string $destinationMachineName, array $displays, array $formDisplaySpecification, string $fieldGroup): void
  {
    $field_storage = FieldStorageConfig::loadByName($sourceType, $fieldName);

    // Create field storage if it doesn't exist.
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create($fieldStorage);
      $field_storage->save();
    }

    $field_config = FieldConfig::loadByName($sourceType, $destinationMachineName, $fieldName);

    // check if field is already there
    if (!$field_config) {
      // Attach the field to the content type.
      $field_config = FieldConfig::create($fieldConfiguration);
      $field_config->save();

      foreach($displays as $displayWanted=>$specification){
        // Search display
        $display = $this->entityTypeManager->getStorage('entity_view_display')
          ->load($displayWanted);
        if ($display) {
          $display->setComponent($fieldName,$specification)->save();
        }
      }

      // Form display settings for the field.
      $form_display = $this->entityTypeManager->getStorage('entity_form_display')
        ->load($sourceType . '.' . $destinationMachineName . '.default');

      $form_display->setComponent($fieldName, $formDisplaySpecification);

      if ($sourceType === 'node') {
        $field_group = $form_display->getThirdPartySetting('field_group', $fieldGroup);
        if ($field_group) {
          $field_group["children"][] = $fieldName;
          $form_display->setThirdPartySetting('field_group', $fieldGroup, $field_group);
        }
      }

      $form_display->save();
    }
  }

  /**
   * @param string $field
   * @param string $fieldLabel
   * @param string $type Default is taxonomy_term, possible values are: 'date', 'string', 'boolean', 'integer', 'decimal', 'taxonomy_term'
   * @param string $termProperty Default is name, possible values are: 'name', 'tid'
   * Example: My field » Taxonomy term » Name
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\search_api\SearchApiException
   */
  public function addToSearchIndex(string $field, string $fieldLabel, string $type = 'taxonomy_term', string $termProperty = 'name'): void
  {
    $moduleHandler = Drupal::service('module_handler');

    if ($moduleHandler->moduleExists('remora_search')) {
      $index = Index::load('default_data');
      $fields = $index->getFields();

      // '_tid' needs to be added to the field name when adding a term ID to the search index because the index will already
      // contain a machine name for the tagging fields e.g. 'field_categories'. This differentiation ensures that
      // the term ID field is uniquely identified within the index.
      $fieldName = $termProperty === 'tid' ? $field . '_tid' : $field;

      if (!isset($fields[$fieldName])) {
        $propertyPath = $field;

        $field_index = new Field($index, $fieldName);
        if($type === 'taxonomy_term') {
          $type = 'string';
          $propertyPath .= ":entity:$termProperty";
        }

        $field_index->setType($type);
        $field_index->setBoost(1.0);
        $field_index->setDatasourceId('entity:node');
        $field_index->setPropertyPath($propertyPath);
        $field_index->setLabel($fieldLabel);
        $index->addField($field_index);
      }

      $index->save();
    }
  }
}
