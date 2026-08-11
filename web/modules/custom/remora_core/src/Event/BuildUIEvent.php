<?php

namespace Drupal\remora_core\Event;

use Drupal\media_library\MediaLibraryState;

class BuildUIEvent
{

  public const EVENT_NAME = 'media_library_build_ui';

  public function __construct(public readonly MediaLibraryState $mediaLibraryState)
  {
  }

  /**
   * Update the list of allowed media types
   * Also sets the selected media type if the allowed media type isn't available
   *
   * @param array $allowedTypeIds
   * @return $this
   */
  public function setAllowedTypeIDs(array $allowedTypeIds): self
  {
    $this->mediaLibraryState->set('media_library_allowed_types', $allowedTypeIds);

    // if the selected type isn't available, select the first allowed type
    if(!in_array($this->mediaLibraryState->getSelectedTypeId(), $allowedTypeIds, true)) {
      $this->mediaLibraryState->set('media_library_selected_type', reset($allowedTypeIds));
    }

    $this->mediaLibraryState->set('hash', $this->mediaLibraryState->getHash());
    return $this;
  }

}
