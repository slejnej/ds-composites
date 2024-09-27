<?php

namespace Drupal\remora_core\Event;

use Drupal;
use Drupal\Component\EventDispatcher\Event;
use Psr\Log\LoggerInterface;

class ReferenceableEntityEvent extends Event
{
  private LoggerInterface $logger;

  public function __construct(private array $contentTypes)
  {
    $this->logger = Drupal::logger('remora_referenceable_entity');
  }

  public function add(string $machineName, string $humanReadableName): self
  {
    // don't crash the site over this but do note it
    if($this->has($machineName)) {
      $this->logger->error("Entity type $machineName has already been registered!");
      return $this;
    }

    $this->contentTypes[$machineName] = $humanReadableName;
    return $this;
  }

  public function remove(string $machineName): self
  {
    unset($this->contentTypes[$machineName]);

    return $this;
  }

  public function has(string $machineName): bool
  {
    return isset($this->contentTypes[$machineName]);
  }

  public function getContentTypes(): array
  {
    return $this->contentTypes;
  }

}
