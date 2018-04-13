<?php

namespace Electro\Plugins\Login\Services;

use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Config\LoginSettings;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Electro\Traits\InspectionTrait;

class DefaultUser implements UserInterface
{
  use InspectionTrait;

  private $active;
  private $id;
  private $lastLogin;
  private $password;
  private $realName;
  private $registrationDate;
  private $role;
  private $token;
  private $email;
  private $username;

  private $db;
  const defaultUserRole = 1;

  static $INSPECTABLE = ['active', 'id', 'lastLogin', 'realName', 'registrationDate', 'role', 'token', 'email', 'password'];
  /**
   * @var LoginSettings
   */
  private $loginSettings;

  public function __construct(ConnectionsInterface $connections, LoginSettings $loginSettings)
  {
    $this->db = $connections->get()->getPdo();
    $this->loginSettings = $loginSettings;
  }

  function activeField($set = null)
  {
    if (isset($set))
      $this->active = $set;
    return $this->active;
  }

  public function findById($id)
  {
    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE id = ?', [$id])->fetch();
    if ($user) {
      $this->fillFields($user);
      return true;
    }
    return false;
  }

  public function findByName($username)
  {
    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$username])->fetchObject();
    if ($user) {
      $this->fillFields($user);
      return true;
    }
    return false;
  }

  public function getRecord()
  {
    return [
      'active' => $this->activeField(),
      'id' => $this->idField(),
      'lastLogin' => $this->lastLoginField(),
      'realName' => $this->realNameField(),
      'registrationDate' => $this->registrationDateField(),
      'role' => $this->roleField(),
      'token' => $this->tokenField(),
      'username' => $this->usernameField(),
    ];
  }

  function getUsers()
  {
    return [];
  }

  function idField($set = null)
  {
    if (isset($set))
      $this->id = $set;
    return $this->id;
  }

  function lastLoginField($set = null)
  {
    if (isset($set))
      $this->lastLogin = $set;
    return $this->lastLogin;
  }

  function onLogin()
  {
    $this->lastLogin = date('Y-m-d H:i:s', time() - 3600);
    $this->db->exec('UPDATE users SET lastLogin = ? WHERE id = ?;', [$this->lastLogin, $this->id]);
  }

  function passwordField($set = null)
  {
    if (isset($set))
      $this->password = password_hash($set, PASSWORD_BCRYPT);
    return $this->password;
  }

  function realNameField($set = null)
  {
    if (isset($set))
      return $this->realName = $set;
    return $this->realName;
  }

  function registrationDateField($set = null)
  {
    if (isset($set))
      $this->registrationDate = $set;
    return $this->registrationDate;
  }

  function roleField($set = null)
  {
    if (isset($set))
      $this->role = $set;
    return $this->role;
  }

  function tokenField($set = null)
  {
    if (isset($set))
      $this->token = $set;
    return $this->token;
  }

  function usernameField($set = null)
  {
    if (isset($set)) {
      $this->username = $set;
      if (is_null($this->realName))
        $this->realName = ucfirst($this->username);
    }
    return $this->username;
  }

  function verifyPassword($password)
  {
    return password_verify($password, $this->password);
  }

  private function fillFields($user)
  {
    $this->active = $user->active;
    $this->id = $user->id;
    $this->lastLogin = $user->lastLogin;
    $this->realName = $user->realName;
    $this->registrationDate = $user->registrationDate;
    $this->role = $user->role;
    $this->token = $user->rememberToken;
    $this->email = $user->email;
    $this->password = $user->password;
  }

  function __sleep()
  {
    return array('active', 'id', 'lastLogin', 'realName', 'registrationDate', 'role', 'token', 'email', 'password');
  }

  function __wakeup()
  {

  }

  function findByEmail($email)
  {
    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$email])->fetchObject();
    if ($user) {
      $this->fillFields($user);
      return true;
    }
    return false;
  }

  /**
   * Removes the user record searching by the email (which may or may not be the primary key).
   *
   * @param string $email
   */
  function removeByEmail($email)
  {
    if ($this->findByEmail($email)) $this->db->exec('DELETE FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$email]);
  }

  /**
   * Register new user record.
   *
   * @param array $data
   */
  function registerUser($data)
  {
    $newPassword = password_hash($data['password'], PASSWORD_BCRYPT);
    $now = date("Y-m-d h:i:s");

    $this->db->exec('INSERT INTO ' . $this->loginSettings->usersTableName . ' (created_at,updated_at,email,password,realName, registrationDate,role,active,rememberToken) VALUES(?,?,?,?,?,?,?,?,?);', [$now, $now, $data['email'], $newPassword, $data['realName'], $now, UserInterface::USER_ROLE_STANDARD, 0, $data['token']]);
  }

  /**
   * Update user rememberToken record.
   *
   * @param string $token
   * @param int $id
   */
  function updateRememberToken($token, $id)
  {
    $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET rememberToken = ? WHERE id = ?;', [$token, $id]);
  }

  /**
   * Finds the user record searching by the rememberToken.
   *
   * @param string $token
   * @return bool True if the user was found.
   */
  function findByRememberToken($token)
  {
    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE rememberToken = ?', [$token])->fetchObject();
    if ($user) {
      $this->fillFields($user);
      return true;
    }
    return false;
  }

  /**
   * Update user password record.
   *
   * @param string $newPassword
   * @param int $id
   */
  function resetPassword($newPassword, $id)
  {
    $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET password = ? WHERE id = ?;', [$newPassword, $id]);
    $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET rememberToken = ? WHERE id = ?;', ["", $id]);
  }

  /**
   * Update user active field.
   *
   * @param string $token
   */
  function setActive($token)
  {
    $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET active= ?, rememberToken = ? WHERE rememberToken = ?', [1, "", $token]);
  }

  /**
   * Get user object by RememberToken.
   *
   * @param string $token
   * @return bool
   */
  function getUserByRememberToken($token)
  {
    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE rememberToken = ?', [$token])->fetchObject();
    if ($user) {
      $this->fillFields($user);
      return $user;
    }
    return false;
  }

  /**
   * Gets or sets the user's email, which may be displayed on the application UI.
   *
   *
   *
   * @param string $set A setter value.
   * @return string
   */
  function emailField($set = null)
  {
    if (isset($set))
      return $this->email = $set;
    return $this->email;
  }
}
