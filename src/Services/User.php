<?php

namespace Electro\Plugins\Login\Services;

use Electro\Authentication\Lib\GenericUser;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Config\LoginSettings;
use Electro\Traits\InspectionTrait;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;

class User extends GenericUser implements UserInterface
{
  use InspectionTrait;

  static  $INSPECTABLE = [
    'active', 'enabled', 'id', 'lastLogin', 'username', 'realName', 'registrationDate', 'updatedAt', 'role', 'token',
    'email',
    'password',
  ];

  const CREATED_AT = 'registrationDate';
  const UPDATED_AT = 'updatedAt';
  public  $active;
  public  $email;
  public  $enabled;
  public  $id;
  public  $lastLogin;
  public  $password;
  public  $realName;
  public  $registrationDate;
  public  $role;
  public  $token;
  public  $username;
  private $db;
  /**
   * @var LoginSettings
   */
  private $loginSettings;

  public function __construct (ConnectionsInterface $connections, LoginSettings $loginSettings)
  {
    $this->db            = $connections->get ()->getPdo ();
    $this->loginSettings = $loginSettings;

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
      $this->db->select ('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE username = ?', [$username])
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

  function onLogin ()
  {
    $this->lastLogin = date ('Y-m-d H:i:s', time () - 3600);
    $this->db->exec ('UPDATE users SET lastLogin = ? WHERE id = ?;', [$this->lastLogin, $this->id]);
  }

  function remove ()
  {
    $this->db->exec ('DELETE FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$this->email]);
  }

  function submit ()
  {
    $now = date ('Y-m-d H:i:s', time () - 3600);
    if (isset($this->id)) {
      $this->db->exec ('UPDATE ' . $this->loginSettings->usersTableName .
                       ' SET updatedAt = ?, active = ?, enabled = ?, lastLogin = ?, realName = ?, role = ?, token = ?, email = ?, password = ? WHERE id = ?;',
        [
          $now, $this->active, $this->enabled, $this->lastLogin, $this->realName,
          $this->role, $this->token,
          $this->email, $this->password, $this->id,
        ]);
    }
    else $this->db->exec ('INSERT INTO ' . $this->loginSettings->usersTableName .
                          ' (registrationDate,updatedAt,email,password,username,realName,role,active,enabled,token) VALUES(?,?,?,?,?,?,?,?,?,?);',
      [
        $now, $now, $this->email, $this->password, $this->username, $this->realName, $this->role,
        $this->active, $this->enabled,
        $this->token,
      ]);
  }

  private function fillFields ($user)
  {
    $this->active           = $user->active;
    $this->id               = $user->id;
    $this->lastLogin        = $user->lastLogin;
    $this->realName         = $user->realName;
    $this->registrationDate = $user->registrationDate;
    $this->updatedAt        = $user->updatedAt;
    $this->role             = $user->role;
    $this->token            = $user->token;
    $this->email            = $user->email;
    $this->password         = $user->password;
    $this->enabled          = $user->enabled;
    $this->username         = $user->username;
  }
}

