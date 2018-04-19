<?php

namespace Electro\Plugins\Login\ViewModels\Login;

use Electro\Interfaces\SessionInterface;
use Electro\Interop\ViewModel;
use Electro\Plugins\Login\Config\LoginSettings;

class Login extends ViewModel
{
  /** @var SessionInterface */
  private $session;

  public function __construct(SessionInterface $session)
  {
    parent::__construct();
    $this->session = $session;
  }

  public function init()
  {
    $session = $this->session;
    $session->reflashPreviousUrl();

    $this->set([
      'username' => $session->getOldInput('username'),
      'email' => $session->getOldInput('email'),
      'password' => $session->getOldInput('password'),
      'lang' => $session->getOldInput('lang'),
    ]);
  }
}
