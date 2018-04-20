<?php

namespace Electro\Plugins\Login\ViewModels\ActivateUser;

use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interop\ViewModel;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;

class AdminActivateUser extends ViewModel
{
  private $loginSettings;
  /**
   * @var UserInterface
   */
  private $user;

  public function __construct(SessionInterface $session, LoginSettings $loginSettings, KernelSettings $kernelSettings, NavigationInterface $navigation, \Swift_Mailer $mailer, UserInterface $user)
  {
    parent::__construct();
    $this->loginSettings = $loginSettings;
    $this->user = $user;
  }

  public function init()
  {
    $token = $this['props']['token'];

    if ($this->user->findByToken($token)) {
      $user = $this->user->getFields ();
      $user['active'] = 1;
      $this->user->mergeFields ($user);
      $this->user->submit();
    }
    $this->set([
      'activateUser' => '$ACTIVATEUSER_SUCCESS']);
  }
}
