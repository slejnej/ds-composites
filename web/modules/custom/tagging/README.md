# Tagging
Contains all the taxonomy vocabularies required for tagging (except for countries which are [here](https://github.com/MRM-Remora/countries_info).)

## Update hook
Update hook in .module file adds all of the fields to the search index if the `remora_search` module is enabled.


## AUTOGENERATE Node tagging fields

On route /admin/config/tagging you can generate tagging fields for nodes which don't have them.
If any of fields exist on node, it won't be added, that is the reason we don't disable options.

Fields will be automatically added to the search index if remora_search module is installed.

## Addition of new fields for autogeneration

In Tagging configuration form you need to add item in fields constant and add specification in fields_references constant in Install Tagging Fields Service.

Example

```php
    'field_categories' => [
      'reference' => 'taxonomy_vocabulary_machine_name',
      'label' => 'Field label',
      'description' => 'Help text for node form.',
      'form_widget' => 'widget_machine_name_for_form_display',
      'parent' => 'group_parent_for_field',
      'weight' => 23,
      'field_group_label' => 'Label for overview - used if doesn't exist,
      'field_group_type' => 'details -> used if doesn't exist,
      'field_group_description' => 'used if doesn't exist
    ]
```
## Installation of fields on module Install

In modules install hook, you can use code to add tagging fields automatically:

```php
  if($isSyncing || !Drupal::hasService('tagging.service.install_fields')) {
    return;
  }

  /** @var \Drupal\tagging\Service\InstallTaggingFieldsService $fieldService */
  $fieldService = Drupal::service('tagging.service.install_fields');
  // Attach the fields to CT.
  $fieldService->installFields($ctMachineName);
  // add fields to the search index
  $fieldService->addSearchFields();

```
