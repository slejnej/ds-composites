<?php

namespace Drupal\tagging\Service;

use Drupal;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\Field;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Used to install fields on CT
 */
class InstallTaggingFieldsService
{

  const FIELDS = [
    'field_categories',
    'field_countries',
    'field_diseases',
    'field_tags',
    'field_topics'
  ];

  const FIELDS_REFERENCES = [
    'field_categories' => [
      'reference' => 'categories',
      'label' => 'Categories',
      'description' => 'Select the most suitable category for this content. This will display on the page and is used to filter search results.',
      'form_widget' => 'cshs',
      'parent' => 'group_overview',
      'weight' => 23,
      'field_group_label' => 'Overview',
      'field_group_type' => 'details',
      'field_group_description' => '',
      'cardinality' => 1
    ],
    'field_countries' => [
      'reference' => 'cit_countries_information',
      'label' => 'Countries',
      'description' => 'Relate your content to one or multiple countries.',
      'form_widget' => 'entity_reference_autocomplete',
      'parent' => 'group_tagging',
      'weight' => 6,
      'field_group_label' => 'Tagging',
      'field_group_type' => 'tab',
      'field_group_description' => 'Tag this page to make it easier to find in the site search.',
      'cardinality' => -1
    ],
    'field_diseases' => [
      'reference' => 'diseases',
      'label' => 'Diseases',
      'description' => 'Relate your content to one or multiple diseases.',
      'form_widget' => 'entity_reference_autocomplete',
      'parent' => 'group_tagging',
      'weight' => 6,
      'field_group_label' => 'Tagging',
      'field_group_type' => 'tab',
      'field_group_description' => 'Tag this page to make it easier to find in the site search.',
      'cardinality' => -1
    ],
    'field_tags' => [
      'reference' => 'tags',
      'label' => 'Tags',
      'description' => 'Relate your content to one or multiple tags.',
      'form_widget' => 'entity_reference_autocomplete',
      'parent' => 'group_tagging',
      'weight' => 6,
      'field_group_label' => 'Tagging',
      'field_group_type' => 'tab',
      'field_group_description' => 'Tag this page to make it easier to find in the site search.',
      'cardinality' => -1
    ],
    'field_topics' => [
      'reference' => 'topics',
      'label' => 'Topics',
      'description' => 'Relate your content to a one or multiple topics.',
      'form_widget' => 'entity_reference_autocomplete',
      'parent' => 'group_tagging',
      'weight' => 6,
      'field_group_label' => 'Tagging',
      'field_group_type' => 'tab',
      'field_group_description' => 'Tag this page to make it easier to find in the site search.',
      'cardinality' => -1
    ]
  ];


  /**
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * TaggingConfigurationForm constructor.
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager)
  {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container): static
  {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Attach the fields to the specified content type.
   *
   * @param string $contentType
   * @return void
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  public function installFields(string $contentType): void
  {
    /** @var Drupal\remora_core\Service\AddNewField $filedService */
    $filedService = Drupal::service('remora_core.service.add_new_field');
    foreach(self::FIELDS as $field) {
      $filedService->createField($field, self::FIELDS_REFERENCES[$field], $contentType, 'node', 'taxonomy_term');
    }
  }


  /**
   * adds missing fields to search
   */
  public function addSearchFields(): void
  {
    /** @var Drupal\remora_core\Service\AddNewField $filedService */
    $filedService = Drupal::service('remora_core.service.add_new_field');
    foreach(self::FIELDS as $field) {
      $filedService->addToSearchIndex($field, self::FIELDS_REFERENCES[$field]['label'] . ' » Taxonomy term » Name');
    }
  }
}
