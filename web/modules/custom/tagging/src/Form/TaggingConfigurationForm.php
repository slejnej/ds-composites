<?php

namespace Drupal\tagging\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

class TaggingConfigurationForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'tagging_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $nodeTypes = NodeType::loadMultiple();

    // Extract node type names and check if they have the field_disease field.
    $options = [];
    foreach($nodeTypes as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select Content Types'),
      '#options' => $options,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $selected_content_types = array_filter($form_state->getValue('content_types'));

    $fieldService = Drupal::service('tagging.service.install_fields');
    // Attach the field to selected content types.
    foreach($selected_content_types as $content_type_id) {
      $fieldService->installFields($content_type_id);
    }
    
    $fieldService->addSearchFields();

    $this->messenger()->addStatus($this->t('Fields added successfully.'));
  }
}
