<?php

namespace Drupal\remora_core\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\search_api\Entity\Index;

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
   * @param array $fieldSpecification
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
  public function createField(string $field, array $fieldSpecification, string $destinationMachineName, string $sourceType, string $destinationType): void
  {
    // Determine field type configuration
    $fieldType = $destinationType === 'boolean' ? 'boolean' : 'entity_reference';

    // Build field storage configuration
    $storageConfig = $this->buildStorageConfig($field, $fieldSpecification, $sourceType, $destinationType, $fieldType);

    // Create or load field storage
    $field_storage = FieldStorageConfig::loadByName($sourceType, $field);
    if (!$field_storage) {
      $field_storage = FieldStorageConfig::create($storageConfig);
      $field_storage->save();
    }

    // Build field configuration
    $fieldConfig = $this->buildFieldConfig($field_storage, $fieldSpecification, $destinationMachineName, $destinationType);

    // Create or load field
    $field_config = FieldConfig::loadByName($sourceType, $destinationMachineName, $field);
    if (!$field_config) {
      $field_config = FieldConfig::create($fieldConfig);
      $field_config->save();
    }

    // Handle displays (reuse existing logic but make it generic)
    $this->configureDisplays($field, $fieldSpecification, $destinationMachineName, $sourceType, $destinationType);
  }

  /**
   * Create a field group structure for a form display.
   *
   * @param string $groupMachineName The machine name of the group (e.g., 'group_product_categories')
   * @param array $groupSpecification Specification for the group
   * @param string $entityType The entity type (e.g., 'node', 'paragraph')
   * @param string $bundle The bundle machine name (e.g., 'related_content')
   * @param string $formMode The form mode (default is 'default')
   */
  public function createFieldGroup(string $groupMachineName, array $groupSpecification, string $entityType, string $bundle, string $formMode = 'default'): void
  {
    // Load the form display
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')
      ->load($entityType . '.' . $bundle . '.' . $formMode);

    if (!$form_display) {
      throw new \Exception("Form display {$entityType}.{$bundle}.{$formMode} not found");
    }

    // Check if group already exists
    $existing_group = $form_display->getThirdPartySetting('field_group', $groupMachineName);

    if ($existing_group) {
      return;
    }

    // Create new group configuration following the same structure as your field creation code
    $group_config = [
      'children' => $groupSpecification['children'] ?? [],
      'label' => $groupSpecification['label'] ?? ucwords(str_replace('_', ' ', $groupMachineName)),
      'region' => $groupSpecification['region'] ?? 'content',
      'parent_name' => $groupSpecification['parent'] ?? '',
      'weight' => $groupSpecification['weight'] ?? 0,
      'format_type' => $groupSpecification['format_type'] ?? 'details',
      'format_settings' => [
        'id' => $groupSpecification['id'] ?? '',
        'classes' => $groupSpecification['classes'] ?? '',
        'show_empty_fields' => $groupSpecification['show_empty_fields'] ?? true,
        'open' => $groupSpecification['open'] ?? false,
        'description' => $groupSpecification['description'] ?? '',
        'required_fields' => $groupSpecification['required_fields'] ?? false,
      ],
    ];

    // For tab format type, ensure proper settings
    if ($group_config['format_type'] === 'tab') {
      $group_config['format_settings']['formatter'] = $groupSpecification['formatter'] ?? ($groupSpecification['open'] === false ? 'closed' : 'open');
    }

    // For tabs container
    if ($group_config['format_type'] === 'tabs') {
      $group_config['format_settings']['direction'] = $groupSpecification['direction'] ?? 'vertical';
      $group_config['format_settings']['width_breakpoint'] = $groupSpecification['width_breakpoint'] ?? 640;
    }

    // Connect parent and group if parent is defined
    if (!empty($group_config['parent_name'])) {
      $parent_name = $group_config['parent_name'];
      $parent = $form_display->getThirdPartySetting('field_group', $parent_name);

      if ($parent) {
        $parent['children'][] = $groupMachineName;
        $parent['children'] = array_unique($parent['children']);

        $form_display->setThirdPartySetting('field_group', $parent_name, $parent);
      }
    }

    // Save the group to the form display
    $form_display->setThirdPartySetting('field_group', $groupMachineName, $group_config);
    $form_display->save();
  }

/**
 * allows generation of every type of field and adding it to correct displays, see example in map_pod
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
  public function addToSearchIndex(string $field, string $fieldLabel, string $type = 'taxonomy_term', string $termProperty = 'name', string $datasourceId = 'entity:node'): void
  {
    // Check if Search API module is installed
    if (!\Drupal::moduleHandler()->moduleExists('search_api')) {
      \Drupal::logger('remora_core')->warning('Search API module is not installed.');
      return;
    }

    // Load the index
    $index = Index::load('default_data');
    if (!$index) {
      \Drupal::logger('remora_core')->warning('Search API index "default_data" not found.');
      return;
    }

    // Determine the final field name.
    $fieldName = $field;

    // Special case: for taxonomy_term IDs, add suffix
    if ($termProperty === 'tid') {
      $fieldName = $field . '_tid';
    }

    // Check if field already exists
    if ($index->getField($fieldName)) {
      return;
    }

    // Determine the property path.
    $propertyPath = $field;
    $fieldType = $type;

    $fieldConfig = [
      'label' => $fieldLabel,
      'type' => $fieldType,
      'datasource_id' => $datasourceId,
      'property_path' => $propertyPath,
    ];

    if ($type === 'taxonomy_term') {
      // For taxonomy terms, property path includes the referenced entity and the specific property.
      $propertyPath = "{$field}:entity:{$termProperty}";

      // Set field type based on term property
      if ($termProperty === 'tid') {
        $fieldType = 'integer';  // Term IDs are integers
      } else {
        $fieldType = 'string';   // Term names are strings
      }

      // Add configuration for value extraction.
      $fieldConfig = [
        'label' => $fieldLabel,
        'type' => $fieldType,
        'datasource_id' => $datasourceId,
        'property_path' => $propertyPath,
        'configuration' => [
          'value_extraction' => "entity:taxonomy_term>{$termProperty}",
        ],
      ];
    }
    elseif ($termProperty !== 'name' && $termProperty !== '') {
      // For nested referenced entity fields (like media artwork).
      $propertyPath = "{$field}:entity:{$termProperty}";
      $fieldConfig = [
        'label' => $fieldLabel,
        'type' => $fieldType,
        'datasource_id' => $datasourceId,
        'property_path' => $propertyPath,
      ];
    }

    // Create and add the field
    $fieldIndex = \Drupal::service('search_api.fields_helper')->createField($index, $fieldName, $fieldConfig);

    $index->addField($fieldIndex);
    $index->save();

    // Optionally trigger reindexing
    if (method_exists($index, 'reindex')) {
      $index->reindex();
    }

    \Drupal::logger('remora_core')->info('Added Search API field @fieldName to index @indexId.', [
      '@fieldName' => $fieldName,
      '@indexId' => $index->id(),
    ]);
  }

  private function buildStorageConfig(string $field, array $spec, string $sourceType, string $destinationType, string $fieldType): array
  {
    $config = [
      'entity_type' => $sourceType,
      'field_name' => $field,
      'type' => $fieldType,
      'cardinality' => $spec['cardinality'] ?? 1,
    ];

    if ($fieldType === 'entity_reference') {
      $config['settings'] = [
        'target_type' => $destinationType,
      ];
    } else {
      $config['settings'] = [
        'on_label' => $spec['on_label'] ?? 'On',
        'off_label' => $spec['off_label'] ?? 'Off',
      ];
    }

    return $config;
  }

  private function buildFieldConfig($field_storage, array $spec, string $bundle, string $destinationType): array
  {
    $config = [
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $spec['label'],
      'required' => $spec['required'] ?? FALSE,
      'description' => $spec['description'] ?? '',
    ];

    if ($destinationType === 'boolean') {
      $config['default_value'] = $spec['default_value'] ?? [];
      $config['settings'] = [
        'on_label' => $spec['on_label'] ?? 'On',
        'off_label' => $spec['off_label'] ?? 'Off',
      ];
    } else {
      $config['settings'] = [
        'handler_settings' => [
          'target_bundles' => [
            $spec['reference'] => $spec['reference'],
          ],
        ],
      ];
    }

    return $config;
  }

  private function configureDisplays(string $field, array $spec, string $bundle, string $sourceType, string $destinationType): void
  {
    // Form display
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')
      ->load($sourceType . '.' . $bundle . '.default');

    // Add to group (works for both node and paragraph)
    if (!empty($spec['parent'])) {
      $this->addFieldToGroup($form_display, $field, $spec['parent']);
    }

    // Set form component
    $form_display->setComponent($field, [
      'type' => $this->getWidgetType($spec, $destinationType),
      'weight' => $spec['weight'] ?? 0,
      'region' => 'content',
      'settings' => $this->getWidgetSettings($spec, $destinationType),
      'third_party_settings' => [],
    ]);

    $form_display->save();

    // View displays for nodes (reuse your existing logic but make it generic)
    if ($sourceType === 'node') {
      $this->configureNodeViewDisplays($field, $bundle);
    }
  }

  private function addFieldToGroup($form_display, string $field, string $parent_group): void
  {
    $field_group = $form_display->getThirdPartySetting('field_group', $parent_group);
    if ($field_group) {
      $field_group['children'][] = $field;
      $field_group['children'] = array_unique($field_group['children']);
      $form_display->setThirdPartySetting('field_group', $parent_group, $field_group);
    }
  }

  private function getWidgetType(array $spec, string $destinationType): string
  {
    if (isset($spec['form_widget'])) {
      return $spec['form_widget'];
    }

    return $destinationType === 'boolean' ? 'boolean_checkbox' : 'options_select';
  }

  private function getWidgetSettings(array $spec, string $destinationType): array
  {
    if ($destinationType === 'boolean') {
      return [
        'display_label' => $spec['display_label'] ?? true,
      ];
    }

    return [];
  }

  private function configureNodeViewDisplays(string $field, string $bundle): void
  {
    $view_modes = [
      'default',
      'search_index',
      'search_rendered_output',
      'card',
      'content_specific_search_index',
      'full',
    ];

    foreach ($view_modes as $mode) {
      $display = $this->entityTypeManager->getStorage('entity_view_display')
        ->load('node.' . $bundle . '.' . $mode);

      if ($display) {
        $settings = [
          'type' => $mode === 'default' ? 'entity_reference_entity_view' : 'entity_reference_label',
          'weight' => 10,
        ];

        if ($mode !== 'default') {
          $settings['label'] = 'hidden';
          $settings['settings'] = ['link' => false];
        }

        $display->setComponent($field, $settings)->save();
      }
    }
  }
}
