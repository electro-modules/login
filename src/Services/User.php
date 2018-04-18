<?php

namespace Electro\Plugins\Login\Services;

use Electro\Authentication\Lib\GenericUser;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Config\LoginSettings;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Electro\Traits\InspectionTrait;

class User extends GenericUser implements UserInterface
{
  use InspectionTrait;

  public $active;
  public $enabled;
  public $id;
  public $lastLogin;
  public $password;
  public $realName;
  public $registrationDate;
  public $role;
  public $token;
  public $email;
  public $username;

  private $db;

  static $INSPECTABLE = [
    'active', 'enabled', 'id', 'lastLogin', 'realName', 'registrationDate', 'role', 'token', 'email', 'password',
  ];
  /**
   * @var LoginSettings
   */
  private $loginSettings;

  public function __construct (ConnectionsInterface $connections, LoginSettings $loginSettings)
  {
    $this->db            = $connections->get ()->getPdo ();
    $this->loginSettings = $loginSettings;

  }

  public function findById ($id)
  {
    $user =
      $this->db->select ('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE id = ?', [$id])->fetch ();
    if ($user) {
      $this->fillFields ($user);
      return true;
    }
    return false;
  }

  public function findByName ($username)
  {
    $user =
      $this->db->select ('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$username])
               ->fetchObject ();

    if ($user) {
      $this->fillFields ($user);
      return true;
    }
    return false;
  }

  public function findByToken ($token)
  {
    $user =
      $this->db->select ('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE token = ?', [$token])
               ->fetchObject ();
    if ($user) {
      $this->fillFields ($user);
      return true;
    }
    return false;
  }

  public function findByEmail ($email)
  {
    $user = $this->db->select ('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$email])
                     ->fetchObject ();
    if ($user) {
      $this->fillFields ($user);
      return true;
    }
    return false;
  }

  public function getRecord ()
  {
    return [
      'active'           => $this->activeField (),
      'id'               => $this->idField (),
      'lastLogin'        => $this->lastLoginField (),
      'realName'         => $this->realNameField (),
      'registrationDate' => $this->registrationDateField (),
      'role'             => $this->roleField (),
      'token'            => $this->tokenField (),
      'username'         => $this->usernameField (),
      'email'            => $this->emailField (),
      'password'         => $this->passwordField (),
      'enabled'          => $this->enabledField (),
    ];
  }

  function setRecord ($data)
  {
    $newPassword = password_hash (get ($data, 'password'), PASSWORD_BCRYPT);

    $email    = get ($data, 'email');
    $realName = get ($data, 'realName');
    $token    = get ($data, 'token');
    $active   = get ($data, 'active', 0);
    $enabled  = get ($data, 'enabled', 1);

    $this->active   = $active;
    $this->enabled  = $enabled;
    $this->password = $newPassword;
    $this->realName = $realName;
    $this->email    = $email;
    $this->token    = $token;
    $this->username = $email;
    $this->role     = UserInterface::USER_ROLE_STANDARD;
  }

  function onLogin ()
  {
    $this->lastLogin = date ('Y-m-d H:i:s', time () - 3600);
    $this->db->exec ('UPDATE users SET lastLogin = ? WHERE id = ?;', [$this->lastLogin, $this->id]);
  }

  private function fillFields ($user)
  {
    $this->active           = $user->active;
    $this->id               = $user->id;
    $this->lastLogin        = $user->lastLogin;
    $this->realName         = $user->realName;
    $this->registrationDate = $user->registrationDate;
    $this->role             = $user->role;
    $this->token            = $user->token;
    $this->email            = $user->email;
    $this->password         = $user->password;
    $this->enabled          = $user->enabled;
    $this->username         = $user->email;
  }

  function remove ()
  {
    $this->db->exec ('DELETE FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$this->email]);
  }

  function submit ()
  {
    $now = date ("Y-m-d h:i:s");
    if (isset($this->id)) {
      $this->db->exec ('UPDATE ' . $this->loginSettings->usersTableName .
                       ' SET active = ?, enabled = ?, lastLogin = ?, realName = ?, registrationDate = ?, role = ?, token = ?, email = ?, password = ? WHERE id = ?;',
        [
          $this->active, $this->enabled, $this->lastLogin, $this->realName, $this->registrationDate,
          $this->role, $this->token,
          $this->email, $this->password, $this->id,
        ]);
    }
    else $this->db->exec ('INSERT INTO ' . $this->loginSettings->usersTableName .
                          ' (created_at,updated_at,email,password,realName, registrationDate,role,active,enabled,token) VALUES(?,?,?,?,?,?,?,?,?,?);',
      [
        $now, $now, $this->email, $this->password, $this->realName, $now, UserInterface::USER_ROLE_STANDARD,
        $this->active, $this->enabled,
        $this->token,
      ]);
  }
}

