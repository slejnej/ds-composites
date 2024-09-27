<?php
namespace Drupal\remora_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\layout_paragraphs\Form\DeleteComponentForm;

class ExtendDeleteComponentForm extends DeleteComponentForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, LayoutParagraphsLayout $layout_paragraphs_layout = NULL, string $component_uuid = NULL): array
  {
    $form = parent::buildForm($form, $form_state, $layout_paragraphs_layout, $component_uuid);

    $component = $this->layoutParagraphsLayout->getComponentByUuid($component_uuid);
    $type = $component->getEntity()->getParagraphType()->label();
    $editorTitle = $component->getEntity()->hasField('field_editor_title') ? $component->getEntity()->get('field_editor_title')->value : '';

    $form['#title'] = $this->t('Delete nugget', ['@type' => $type]);
    $form['confirm'] = [
      '#markup' => $this->t(
        'Are you sure you want to delete this <i>@editorTitle [@type nugget]</i> ? There is no undo.',
        ['@editorTitle' => $editorTitle, '@type' => $type]
      ),
    ];

    return $form;
  }
}
