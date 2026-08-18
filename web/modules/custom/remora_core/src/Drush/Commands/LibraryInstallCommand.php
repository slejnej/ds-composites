<?php

namespace Drupal\remora_core\Drush\Commands;

use Drush\Attributes\Command;
use Drush\Commands\DrushCommands;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Drush command file
 *
 * Class LibraryInstallCommand
 * @package Drupal\remora_core\Commands
 */
class LibraryInstallCommand extends DrushCommands
{

  #[Command(name: 'remora:library:install', aliases: ['rli'])]
  public function install_library($options = ['library' => null])
  {
    $libraryName = $options['library'];

    $sourceDir = \Drupal::service('file_system')->realpath('remora_core://libraries');

    // if no library name then list all available
    if (empty($libraryName)) {
      $this->output()->writeln('<error>Available libraries:</error>');
      $files = array_diff(scandir($sourceDir), ['.', '..']);

      foreach ($files as $file) {
        $this->output()->writeln(sprintf('- %s', $file));
      }

      return;
    }

    $source = sprintf('%s/%s', $sourceDir, $libraryName);
    $pathParts = pathinfo($source);
    $destination = sprintf('%s/libraries/%s', DRUPAL_ROOT, $pathParts['filename']);

    # delete directory if exists
    if (is_dir($destination)) {
      $del = ['rm', '-rf', $destination];
      $process = new Process($del);
      $process->run();

      if (!$process->isSuccessful()) {
        throw new ProcessFailedException($process);
      }

      $this->output()->writeln('<comment>Old library DELETED successfully.</comment>');
    }

    $scriptsDir = \Drupal::service('file_system')->realpath('remora_core://scripts');
    $unzip = [
      '/bin/bash',
      sprintf("%s/unzip.sh", $scriptsDir),
      $source,
      sprintf('%s/libraries', DRUPAL_ROOT),
      $pathParts['filename']
    ];

    $process = new Process($unzip);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $this->output()->writeln(sprintf('<comment>Library `%s` successfully installed.</comment>', $pathParts['filename']));
  }
}