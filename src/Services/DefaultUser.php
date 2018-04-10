<?php

namespace Electro\Plugins\Login\Services;

use Electro\Interfaces\UserInterface;
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

    private $db;
    private $usersTableName = 'users';

    static $INSPECTABLE = ['active', 'id', 'lastLogin', 'realName', 'registrationDate', 'role', 'token', 'email', 'password'];

    public function __construct(ConnectionsInterface $connections)
    {
        $this->db = $connections->get()->getPdo();
    }

    function activeField($set = null)
    {
        if (isset($set))
            $this->active = $set;
        return $this->active;
    }

    public function findById($id)
    {
        $user = $this->db->select('SELECT * FROM ' . $this->usersTableName . ' WHERE id = ?', [$id])->fetch();
        if ($user) {
            $this->fillFields($user);
            return true;
        }
        return false;
    }

    public function findByName($username)
    {
        $user = $this->db->select('SELECT * FROM ' . $this->usersTableName . ' WHERE email = ?', [$username])->fetchObject();
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
        $this->lastLogin = date('Y-m-d H:i:s', time()-3600);
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
        $this->realName =$user->realName;
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


}
