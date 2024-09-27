<?php

namespace Drupal\remora_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class RemoraSettingsForm extends ConfigFormBase
{
  public const FORM_ID = 'remora_settings';
  public const CONFIG_ID = 'remora_core.remora_settings';

  /**
   * @return string[]
   */
  protected function getEditableConfigNames(): array
  {
    return [self::CONFIG_ID];
  }

  public function getFormId(): string
  {
    return self::FORM_ID;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $config = $this->config(self::CONFIG_ID);

    // Add a field group for Footer settings.
    $form['footer_settings'] = [
      '#type' => 'details',
      '#title' => t('Footer settings'),
      '#open' => TRUE,
      '#group' => 'footer_settings_group', // Adding a group to place fields inside this details element.
    ];

    // Load the 'Footer' site setting entity.
    $entityTypeManager = \Drupal::entityTypeManager();
    $siteSettingStorage = $entityTypeManager->getStorage('site_setting_entity');

    // Fetch the 'Footer' site setting entity by its name.
    $footerEntity = $siteSettingStorage->loadByProperties(['name' => 'Footer']);

    // Get the first found 'Footer' site setting entity.
    $footerEntity = reset($footerEntity);

    // Build the URL for the 'Footer' site setting edit form if the entity is available.
    $footerEditURL = null;
    if($footerEntity) {
      $footerEditURL = $footerEntity->toUrl('edit-form')->toString();
    }

    $footerMessage = $footerEditURL ? '<p><a href="' . $footerEditURL . '">Update the content of your footer</a></p>' : '<p>Footer settings not found.</p>';

    $form['footer_settings']['footer_message'] = [
      '#markup' => $footerMessage,
    ];

    $form['card_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Card settings'),
      '#open' => TRUE,
    ];

    $form['card_settings']['card_button_text'] = [
      '#type' => 'textfield',
      '#title' => t('Default card button text'),
      '#default_value' => $config->get('card_button_text'),
      '#required' => false,
      '#description' => t("If you would like to have buttons on your cards throughout this site, add your default button text here. You can override this default text on individual cards if needed."),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $this->configFactory()->getEditable(self::CONFIG_ID)
      ->set('card_button_text', $form_state->getValue('card_button_text'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}