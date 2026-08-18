<?php

namespace Drupal\remora_core\Plugin\Linkit\Matcher;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Provides linkit node matcher with multi-lingual support.
 *
 * @Matcher(
 *   id = "remora_core:linkit_node",
 *   label = @Translation("[Remora] Content"),
 *   target_entity = "node",
 *   provider = "node"
 * )
 */
class NodeMatcher extends \Drupal\linkit\Plugin\Linkit\Matcher\NodeMatcher
{
  /**
   * {@inheritDoc}
   */
  public function execute(mixed $string): SuggestionCollection
  {
    $suggestions = new SuggestionCollection();
    $query = $this->buildEntityQuery($string);
    $query->accessCheck(TRUE);
    $query_result = $query->execute();
    $url_results = $this->findEntityIdByUrl($string);
    $result = array_merge($query_result, $url_results);

    // If no results, return an empty suggestion collection.
    if (empty($result)) {
      return $suggestions;
    }

    $entities = $this->entityTypeManager->getStorage($this->targetType)->loadMultiple($result);

    foreach ($entities as $entity) {
      // Check the access against the defined entity access handler.
      /** @var AccessResultInterface $access */
      $access = $entity->access('view', $this->currentUser, TRUE);

      if (!$access->isAllowed()) {
        continue;
      }

      foreach($entity->getTranslationLanguages() as $langcode => $language) {
        $suggestion = $this->createSuggestion($entity->getTranslation($langcode));
        $suggestions->addSuggestion($suggestion);
      }
    }

    return $suggestions;
  }

  /**
   * {@inheritDoc}
   */
  protected function buildPath(EntityInterface $entity): string
  {
    return $entity->toUrl('canonical', ['path_processing' => true])->toString();
  }

}
