<?php

namespace awsPluginInstaller\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\InstalledRepositoryInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
  protected Composer $composer;
  protected IOInterface $io;
  protected string $baseName;
  protected bool $fileLogging;

  public function activate(Composer $composer, IOInterface $io)
  {
    $this->composer = $composer;
    $this->io = $io;
    $this->fileLogging = false;
  }

  public function deactivate(Composer $composer, IOInterface $io)
  {
    // TODO: Implement deactivate() method. Should probably remove all files that were moved during install
  }

  public function uninstall(Composer $composer, IOInterface $io)
  {
    // TODO: Implement deactivate() method. Should probably remove all files that were moved during install
  }

  /**
   * Define events that this code will react to
   *
   * @return \array[][]
   */
  public static function getSubscribedEvents(): array
  {
    return [
      "post-package-install" => [
        ['onPackageInstall', 0]
      ],
      "post-package-update" => [
        ['onPackageUpdate', 0]
      ],
    ];
  }

  /**
   * When new package is installed
   *
   * @param PackageEvent $event
   * @return void
   */
  public function onPackageInstall(PackageEvent $event): void
  {
    /** @var InstallOperation $op */
    $op = $event->getOperation();

    $this->processPackage($op->getPackage());
  }

  /**
   * When the package is updated
   *
   * @param PackageEvent $event
   * @return void
   */
  public function onPackageUpdate(PackageEvent $event): void
  {
    /** @var UpdateOperation $op */
    $op = $event->getOperation();
    $this->processPackage($op->getTargetPackage());
  }

  /**
   * Checks if the package is of the correct type, and if so does the moving
   *
   * @param PackageInterface $package
   * @return void
   */
  private function processPackage(PackageInterface $package): void
  {
    // filter package type for now
    if ($package->getType() === 'mrm-template') {
      $this->doTheMove($package);
    }
  }
  /**
   * Main function that does the moving and renaming
   *
   * @param PackageInterface $package
   * @return void
   */
  private function doTheMove(PackageInterface $package): void
  {
    // gather all the information about the package
    $info = [];

    // composer doesn't care about uppercase and so shouldn't we
    $info['package'] = strtolower($package->getName());
    $info['vendor'] = substr($info['package'], 0, strpos($info['package'], '/'));
    $info['type'] = $package->getType();
    $info['dir'] = sprintf("vendor/%s", $info['package']);

    # absolute urls - will be the project this is added to
    $projectDir = getcwd();
    $packageDir = $info['dir'];

    # get project name from build.xml if exists
    $buildFile = sprintf("%s/build.xml", $projectDir);
    if (file_exists($buildFile)) {
      $xml = simplexml_load_file($buildFile);
      $this->baseName = (string) $xml['name'];
    } else {
      $this->baseName = basename($projectDir);
    }

    $extra = $package->getExtra();

    # log to files
    $this->fileLogging = $extra['installer-logging'];

    $this->wh_log(sprintf('----- %s -----', $info['package']));

    # replace first the variables if needed
    if (isset($extra['replace-variables'])) {
      foreach ($extra['replace-variables'] as $group) {
        $dirToProcess = sprintf("%s/%s", $packageDir, array_key_first($group));
        $pair = reset($group);
        $variable = array_key_first($pair);
        $replacement = reset($pair);

        # special case for replacement
        if ($replacement === 'project_name') {
          $replacement = strtolower($this->baseName);
        } elseif ($replacement === 'PROJECT_NAME') {
          $replacement = $this->baseName;
        } elseif ($replacement === 'theme_name') {
            $theme_dir = '';
            if (file_exists($projectDir . '/web/themes/custom') && $dir = new \DirectoryIterator($projectDir . '/web/themes/custom')) {
                foreach ($dir as $fileinfo) {
                    if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                        $theme_dir = $fileinfo->getFilename();
                        break;
                    }
                }
            }
            $replacement = $theme_dir ? '/themes/custom/' . $theme_dir : '/themes/contrib/remora_base_theme';
        }

        $this->processVariables($dirToProcess, $variable, $replacement);
      }
    }

    if (isset($extra['move-paths'])) {
      foreach ($extra['move-paths'] as $movePath) {
        $from = sprintf("%s/%s", $packageDir, array_key_first($movePath));
        $to = reset($movePath);
        $to = $to[0] === '/' ? $projectDir . $to : $to;

        $this->recursiveMove($from, $to);
      }
    }

    $this->wh_log('----- END -----');
  }

  /**
   * Function that does the creating and walking through directories on all levels
   *
   * @param string $src
   * @param string $dest
   * @return void
   */
  private function recursiveMove(string $src, string $dest): void
  {
    $singleFile = is_file($src);

    // if source is not a directory and not a file (PEBKAC)
    if (!is_dir($src) && !$singleFile) {
      $this->io->error(sprintf("Source can not be processed: %s", $src));
      return;
    }

    if ($singleFile) {
      $srcFile = basename($src);
      $src = dirname($src);
      $destFile = basename($dest);
      $dest = dirname($dest);
    }

    // if the destination directory does not exist create it
    if (!is_dir($dest)) {
      if (!mkdir($dest, 0777, true)) {
        // if the destination directory could not be created stop processing
        $this->io->error("Can't create destination path: {$dest}");
        return;
      }
    }

    if ($singleFile) {
      // proces this single file
      $f = new \SplFileInfo(sprintf("%s/%s", $src, $srcFile));
      $this->moveTheFile($f, sprintf("%s/%s", $dest, $destFile));

    } else {
      // open the source directory to read in files and do all of them
      $i = new \DirectoryIterator($src);
      foreach ($i as $f) {
        if ($f->isFile()) {
          $this->moveTheFile($f, $dest);
        } else if (!$f->isDot() && $f->isDir()) {
          $this->recursiveMove($f->getRealPath(), "$dest/$f");
        }
      }
    }
  }

  /**
   * Function does actual move ov a single file with same or different name
   *
   * @param $f
   * @param $dest
   * @return void
   */
  private function moveTheFile($f, $dest): void
  {
    // can move single file to a different name, have to check here
    if (pathinfo($dest, PATHINFO_EXTENSION)) {
      $to = $dest;
    } else {
      $to = sprintf('%s/%s', $dest, $f->getFilename());
    }

    // to prevent overrides of files outside of vendor that might be custom only move if not exist!!!
    if (!file_exists($to)) {
      $this->wh_log(sprintf("Moving file %s to %s", $f->getRealPath(), $to));
      rename($f->getRealPath(), $to);
    }
  }

  /**
   * Directory walking and replacing variables in files
   *
   * @param string $folder
   * @param string $variable
   * @param string $replacement
   * @return void
   */
  private function processVariables(string $folder, string $variable, string $replacement): void
  {
    // if source is not a directory stop processing
    if (!is_dir($folder)) {
      $this->io->error(sprintf("No directory: %s", $folder));
      return;
    }

    // open the source directory to read in files
    $i = new \DirectoryIterator($folder);
    foreach ($i as $f) {
      if ($f->isFile()) {

        // make sure every time is diff port !
        if ($replacement === 'random_port') {
          file_put_contents($f->getRealPath(),
            preg_replace_callback(
              "/" . $variable . "/",
              function ($matches) {
                return rand(6000, 65535);
              },
              file_get_contents($f->getRealPath()),
              -1,
              $numReplaced
            )
          );

        } else {
          // edit the file
          file_put_contents($f->getRealPath(),
            preg_replace(
              "/" . $variable . "/",
              $replacement,
              file_get_contents($f->getRealPath()),
              -1,
              $numReplaced
            )
          );
        }
        if ($numReplaced) {
          $this->wh_log(sprintf('Replaced in file: %s, find: %s, replace with %s',
            $f->getRealPath(), $variable, $replacement));
        }
      } else if (!$f->isDot() && $f->isDir()) {
        $this->processVariables($f->getRealPath(), $variable, $replacement);
      }
    }
  }

  /**
   * File logging that can be activated
   *
   * @param string $log_msg
   * @return void
   */
  private function wh_log(string $log_msg)
  {
    if ($this->fileLogging) {
      $log_file_data = 'plugin-installer_' . date('d-M-Y') . '.log';
      file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }
  }
}
