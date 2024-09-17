<?php

namespace Drupal\installation_profile\Exception;

use Exception;
use Throwable;

class PathExistsException extends Exception
{
  public function __construct(string $path , int $code = 0, ?Throwable $previous = null)
  {
    $message = sprintf('Path "%s" already exists', $path);
    parent::__construct($message, $code, $previous);
  }

}
