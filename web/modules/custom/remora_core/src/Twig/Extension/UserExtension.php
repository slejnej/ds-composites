<?php

namespace Drupal\remora_core\Twig\Extension;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * Twig extension.
 */
class UserExtension extends AbstractExtension {

  public function getTests(): array
  {
    return [
      'assigned_role' => new TwigTest('assigned_role', [$this, 'assignedRole']),
      'assigned_any_role' => new TwigTest('assigned_any_role', [$this, 'assignedAnyRole']),
      'assigned_all_roles' => new TwigTest('assigned_all_roles', [$this, 'assignedAllRoles'])
    ];
  }

  /**
   * Returns whether the user has the specified role
   *
   * @param AccountInterface $user
   * @param string $role
   * @usage {% if user is assigned_role('role1') %}
   *
   * @return bool True if the user has the role, false otherwise
   */
  public function assignedRole(AccountInterface $user, string $role): bool
  {
    return User::load($user->id())->hasRole($role);
  }

  /**
   * Returns whether the user has any of the specified roles
   *
   * @param AccountInterface $user
   * @param string[] $roles
   * @usage {% if user is assigned_any_role('role1', 'role2') %}
   *
   * @return bool True if the user has any of the roles, false otherwise
   */
  public function assignedAnyRole(AccountInterface $user, string ...$roles): bool
  {
    foreach ($roles as $role) {
      if ($this->assignedRole($user, $role)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Returns whether the user has all the specified roles
   *
   * @param AccountInterface $user
   * @param string[] $roles
   * @usage {% if user is assigned_all_roles('role1', 'role2') %}
   *
   * @return bool True if the user has all roles, false otherwise
   */
  public function assignedAllRoles(AccountInterface $user, string ...$roles): bool
  {
    foreach ($roles as $role) {
      if(!$this->assignedRole($user, $role)) {
        return false;
      }
    }

    return true;
  }
}
