<?php

namespace Electro\Plugins\Login\ViewModels\ActivateUser;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interop\ViewModel;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use Electro\Plugins\Login\Controllers\Register\RegisterController;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Swift_Message;

class AdminActivateUser extends ViewModel
{
  private $loginSettings;
  private $db;

  public function __construct(SessionInterface $session, LoginSettings $loginSettings, ConnectionsInterface $connections, KernelSettings $kernelSettings, NavigationInterface $navigation, \Swift_Mailer $mailer)
  {
    parent::__construct();
    $this->db = $connections->get()->getPdo();
    $this->loginSettings = $loginSettings;
  }

  public function init()
  {
    $token = $this['props']['token'];

    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE rememberToken = ?', [$token])->fetchObject();

    if ($user) {
      $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET active= ?, rememberToken = ? WHERE rememberToken = ?', [1, "", $token]);
    }
    $this->set([
      'activateUser' => '$ACTIVATEUSER_SUCCESS']);
  }
}
