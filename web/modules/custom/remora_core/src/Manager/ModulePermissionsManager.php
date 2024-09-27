<?php

namespace Drupal\remora_core\Manager;

use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\user\Entity\Role;
use Drupal\user\PermissionHandlerInterface;
use Psr\Log\LoggerInterface;

class ModulePermissionsManager
{
  /** @var array All permissions known to the system */
  private readonly array $knownPermissions;
  private array $roles = [];

  public function __construct(private readonly FileSystemInterface $fileSystem, private readonly LoggerInterface $logger, PermissionHandlerInterface $permissionHandler)
  {
    $this->knownPermissions = $permissionHandler->getPermissions();
  }

  /**
   * Reads the permissions.yml file for the module and grants the permissions to the roles after validating the module depencencies (if present)
   *
   * @param string $moduleName The module's machinename to handle permissions for
   * @return bool True if the permissions file exists, false otherwise
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  public function handleModulePermissions(string $moduleName): bool
  {
    $permFile = $this->fileSystem->realpath("$moduleName://config/remora/permissions.yml");
    if(!$permFile) {
      return false;
    }

    $this->logger->info('Syncing permissions for module ' . $moduleName);

    // parse the YAML file
    $permissionsByRole = Yaml::decode(file_get_contents($permFile));

    // if there are dependencies, and they don't validate, exit early
    if(isset($permissionsByRole['dependencies']['module']) && !$this->validateDependencies($permissionsByRole)) {
      return false;
    }

    // remove the dependencies key and loop over all the roles
    unset($permissionsByRole['dependencies']);

    foreach($permissionsByRole as $roleName => $permissions) {
      // grab the role, if it doesn't exist log the error and move on
      $role = $this->roles[$roleName] ?? null;

      if(!isset($role)) {
        $role = $this->roles[$roleName] = Role::load($roleName);
      }

      if(!$role) {
        $this->logger->error("Role $roleName does not exist");
        continue;
      }

      // grant all the permissions to the role
      foreach($permissions as $permission) {
        // check if permission is a real permission
        if(!isset($this->knownPermissions[$permission])) {
          $this->logger->error("Permission $permission does not exist");
          continue;
        }

        $role->grantPermission($permission);
      }

      $role->save();
      $this->logger->info("Granted permissions to role $roleName");
    }

    return true;
  }

  /**
   * Validates all the dependencies are enabled for the permissions
   *
   * @param mixed $permissionsByRole
   * @return bool
   */
  private function validateDependencies(mixed $permissionsByRole): bool
  {
    $moduleHandler = Drupal::moduleHandler();
    foreach($permissionsByRole['dependencies']['module'] as $dependency) {
      if(!$moduleHandler->moduleExists($dependency)) {
        $this->logger->error("Missing dependency: $dependency");
        return false;
      }
    }

    return true;
  }

}
