<?php

namespace Electro\Plugins\Login\Controllers;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Config\LoginSettings;
use Psr\Http\Message\ServerRequestInterface;

class ResetPasswordController
{
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;
  /**
   * @var LoginSettings
   */
  private $loginSettings;

  function __construct(SessionInterface $session, UserInterface $user, RedirectionInterface $redirection, \Swift_Mailer $mailer, LoginSettings $loginSettings)
  {
    $this->session = $session;
    $this->user = $user;
    $this->redirection = $redirection;
    $this->mailer = $mailer;
    $this->loginSettings = $loginSettings;
  }

  /* Verificar Token do url, se é valido ou não, se não for faz redirect para a página de recuperação de palavra-passe base*/
  public static function validateToken(ServerRequestInterface $request, $response, $next, UserInterface $user)
  {
    if ($user->findByRememberToken($request->getAttribute('@token'))) {
      return $next();
    }
    return redirectTo('login');
  }

  function resetPassword($data, $token, ServerRequestInterface $request)
  {
    $password = get($data,'password');
    $password2 = get($data,'password2');

    if (empty($password) || empty($password2))
      throw new AuthenticationException('$RESETPASSWORD_MISSINGINFO', FlashType::ERROR);

    if ($password == $password2) {
      if ($this->user->findByRememberToken($token)) {
        $this->user->passwordField($password);
        $this->user->tokenField("");
        $this->user->submit();
        $this->session->flashMessage('$RESETPASSWORD_SUCCESS_PASS', FlashType::SUCCESS);
        return redirectTo('login');
      }

    } else
      throw new AuthenticationException('$RESETPASSWORD_ERROR_PASS', FlashType::ERROR);
  }
}

