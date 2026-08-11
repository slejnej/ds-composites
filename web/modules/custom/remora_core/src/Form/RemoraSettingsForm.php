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
    $languages = \Drupal::languageManager()->getLanguages();

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

    $form['global_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Global text settings'),
      '#open' => TRUE,
    ];

    $form['global_settings']['show_breadcrumbs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show breadcrumbs'),
      '#default_value' => $config->get('show_breadcrumbs') ?? TRUE,
      '#description' => $this->t('Enable or disable breadcrumbs display across the site.'),
    ];

    $form['global_settings']['card_button_text_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Default card button text'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];

    foreach ($languages as $langcode => $language) {
      $stored = $config->get("card_button_text.$langcode");

      $form['global_settings']['card_button_text_group'][$langcode] = [
        '#type' => 'textfield',
        '#title' => $language->getName(),     // clean and simple
        '#default_value' => $stored ?: '',
        '#description' => $this->t('Text shown when viewing the site in @lang.', [
          '@lang' => $language->getName(),
        ]),
        '#prefix' => '<div class="remora-lang-field">',
        '#suffix' => '</div>',
      ];
    }


    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $values = $form_state->getValue('card_button_text_group');
    $show_breadcrumbs = $form_state->getValue('show_breadcrumbs');

    $this->configFactory()
      ->getEditable(self::CONFIG_ID)
      ->set('card_button_text', $values)
      ->set('show_breadcrumbs', $show_breadcrumbs)
      ->save();

    parent::submitForm($form, $form_state);
  }
}