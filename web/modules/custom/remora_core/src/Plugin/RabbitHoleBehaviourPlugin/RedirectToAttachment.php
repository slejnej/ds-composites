<?php

namespace Drupal\remora_core\Plugin\RabbitHoleBehaviorPlugin;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects to the first attachment in field_<CT>_attachments.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "redirect_to_attachment",
 *   label = @Translation("Redirect to attachment")
 * )
 */
class RedirectToAttachment extends RabbitHoleBehaviorPluginBase
{

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity, Response $currentResponse = NULL)
  {
    // Only for nodes
    if ($entity->getEntityTypeId() !== 'node') {
      return $currentResponse;
    }

    // Get attachment field from content type, if empty just display the page
    $fieldName = sprintf('field_%s_attachments', $entity->bundle());
    if (!$entity->hasField($fieldName)) {
      return $currentResponse;
    }

    // Extract the attachment needed to redirect to
    /** @var FieldItemListInterface $items */
    $items = $entity->get($fieldName);
    $target = $this->extractAttachmentUrl($items);

    // If attachment is empty just display the page.
    if (!$target) {
      return $currentResponse;
    }

    return new TrustedRedirectResponse($target, 302);
  }

  /**
   * Extract a URL from an attachments field.
   */
  protected function extractAttachmentUrl(FieldItemListInterface $items): ?string
  {
    $item = $items->first();
    if (!$item || $item->isEmpty()) {
      return null;
    }

    $media = $item->entity;
    if (!$media instanceof MediaInterface) {
      return null;
    }

    $file = $media->get('field_media_file')->entity ?? NULL;
    if (!$file instanceof FileInterface) {
      return null;
    }

    return Drupal::service('file_url_generator')->generateString($file->getFileUri());
  }

}