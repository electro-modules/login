<?php

namespace Electro\Plugins\Login\ViewModels\Register;

use Electro\Interfaces\SessionInterface;
use Electro\Interop\ViewModel;

class Register extends ViewModel
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
      'realName' => $session->getOldInput('realName'),
      'email' => $session->getOldInput('email'),
      'password' => $session->getOldInput('password'),
      'password2' => $session->getOldInput('password2'),
      'lang' => $session->getOldInput('lang'),
    ]);
  }

}
