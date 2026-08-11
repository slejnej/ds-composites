<?php

namespace Drupal\remora_core\Plugin\RabbitHoleBehaviorPlugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects to the content type link field if set.
 *
 * @RabbitHoleBehaviorPlugin(
 *   id = "redirect_to_link_field",
 *   label = @Translation("Redirect to Link field")
 * )
 */
class RedirectToLinkField extends RabbitHoleBehaviorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function performAction(EntityInterface $entity, Response $currentResponse = NULL) {
    // Only for nodes
    if ($entity->getEntityTypeId() !== 'node') {
      return $currentResponse;
    }

    // Get link field from content type, if empty just display the page
    $fieldName = sprintf('field_%s_link', $entity->bundle());
    if (!$entity->hasField($fieldName)) {
      return $currentResponse;
    }

    // Extract the link needed to redirect to
    /** @var FieldItemListInterface $items */
    $items = $entity->get($fieldName);
    $target = $this->extractUrlFromLinkField($items);

    // If link is empty just display the page.
    if (!$target) {
      return $currentResponse;
    }

    return new TrustedRedirectResponse($target, 302);
  }

  /**
   * Extracts a usable URL string from a Link field.
   */
  protected function extractUrlFromLinkField(FieldItemListInterface $items): ?string
  {
    $item = $items->first();
    if (!$item || $item->isEmpty()) {
      return NULL;
    }

    try {
      $url = $item->getUrl();
      return $url ? $url->toString() : NULL;
    } catch (\Throwable $e) {
      return null;
    }
  }

}
