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
			if (isset($set)) $this->active = $set;
			return $this->active;
		}

		public function findById($id)
		{
			$user = $this->db->select('SELECT * FROM '.$this->loginSettings->usersTableName.' WHERE id = ?', [$id])->fetch();
			if ($user) {
				$this->fillFields($user);
				return true;
			}
			return false;
		}

		public function findByName($username)
		{
			$user = $this->db->select('SELECT * FROM '.$this->loginSettings->usersTableName.' WHERE email = ?', [$username])->fetchObject();
			if ($user) {
				$this->fillFields($user);
				return true;
			}
			return false;
		}

		public function getRecord()
		{
			return ['active' => $this->activeField(), 'id' => $this->idField(), 'lastLogin' => $this->lastLoginField(), 'realName' => $this->realNameField(), 'registrationDate' => $this->registrationDateField(), 'role' => $this->roleField(), 'rememberToken' => $this->tokenField(), 'username' => $this->usernameField(), 'email' => $this->emailField(), 'password' => $this->passwordField()];
		}

		function setRecord($data)
		{
			$newPassword = password_hash(get($data, 'password'), PASSWORD_BCRYPT);

			$email = get($data, 'email');
			$realName = get($data, 'realName');
			$token = get($data, 'token');

			$this->active = 0;
			$this->password = $newPassword;
			$this->realName = $realName;
			$this->email = $email;
			$this->token = $token;
			$this->username = $email;
			$this->role = UserInterface::USER_ROLE_STANDARD;
		}

		function getUsers()
		{
			return [];
		}

		function idField($set = null)
		{
			if (isset($set)) $this->id = $set;
			return $this->id;
		}

		function lastLoginField($set = null)
		{
			if (isset($set)) $this->lastLogin = $set;
			return $this->lastLogin;
		}

		function onLogin()
		{
			$this->lastLogin = date('Y-m-d H:i:s', time() - 3600);
			$this->db->exec('UPDATE users SET lastLogin = ? WHERE id = ?;', [$this->lastLogin, $this->id]);
		}

		function passwordField($set = null)
		{
			if (isset($set)) $this->password = password_hash($set, PASSWORD_BCRYPT);
			return $this->password;
		}

		function realNameField($set = null)
		{
			if (isset($set)) return $this->realName = $set;
			return $this->realName;
		}

		function registrationDateField($set = null)
		{
			if (isset($set)) $this->registrationDate = $set;
			return $this->registrationDate;
		}

		function roleField($set = null)
		{
			if (isset($set)) $this->role = $set;
			return $this->role;
		}

		function tokenField($set = null)
		{
			if (isset($set)) $this->token = $set;
			return $this->token;
		}

		function usernameField($set = null)
		{
			if (isset($set)) {
				$this->username = $set;
				if (is_null($this->realName)) $this->realName = ucfirst($this->username);
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
			$user = $this->db->select('SELECT * FROM '.$this->loginSettings->usersTableName.' WHERE email = ?', [$email])->fetchObject();
			if ($user) {
				$this->fillFields($user);
				return true;
			}
			return false;
		}

		function remove()
		{
			$this->db->exec('DELETE FROM '.$this->loginSettings->usersTableName.' WHERE email = ?', [$this->email]);
		}

		function findByRememberToken($token)
		{
			$user = $this->db->select('SELECT * FROM '.$this->loginSettings->usersTableName.' WHERE rememberToken = ?', [$token])->fetchObject();
			if ($user) {
				$this->fillFields($user);
				return true;
			}
			return false;
		}

		function emailField($set = null)
		{
			if (isset($set)) return $this->email = $set;
			return $this->email;
		}

		function submit()
		{
			$now = date("Y-m-d h:i:s");
			if (isset($this->id)) $this->db->exec('UPDATE '.$this->loginSettings->usersTableName.' SET active = ?, id = ?, lastLogin = ?, realName = ?, registrationDate = ?, role = ?, rememberToken = ?, email = ?, password = ? WHERE id = ?;', [$this->active, $this->id, $this->lastLogin, $this->realName, $this->registrationDate, $this->role, $this->token, $this->email, $this->password, $this->id]);
			else $this->db->exec('INSERT INTO '.$this->loginSettings->usersTableName.' (created_at,updated_at,email,password,realName, registrationDate,role,active,rememberToken) VALUES(?,?,?,?,?,?,?,?,?);', [$now, $now, $this->email, $this->password, $this->realName, $now, UserInterface::USER_ROLE_STANDARD, 0, $this->token]);
		}
	}
