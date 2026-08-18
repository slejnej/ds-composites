<?php

namespace Drupal\remora_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PalettesForm extends ConfigFormBase
{
  /**
   * @return string[]
   */
  protected function getEditableConfigNames(): array
  {
    return ['remora_core.settings'];
  }

  /**
   * @return string
   */
  public function getFormId(): string
  {
    return 'palettes';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return mixed
   */
  public function buildForm(array $form, FormStateInterface $form_state): mixed
  {
    $config = $this->config('remora_core.settings');

    $form['palettes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Palettes'),
      '#default_value' => $config->get('palettes') ?? '',
      '#description' => $this->t('Enter key-value pairs separated by a delimiter.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $values = $form_state->getValues();
    $this->config('remora_core.settings')
      ->set('palettes', $values['palettes'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
