<?php

namespace Electro\Plugins\Login\Controllers\Login;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Utils\SendEmail;
use Psr\Http\Message\ServerRequestInterface;

class LoginController
{
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;

  /**
   * Attempts to log in the user with the given credentials.
   *
   * @param string $username
   * @param string $password
   * @throws AuthenticationException If the login fails.
   */
  function doLogin($username, $password)
  {
    if (empty($username))
      throw new AuthenticationException ('$LOGIN_MISSING_INFO');
    else {
      $user = $this->user;
      if (!$user->findByName($username))
        throw new AuthenticationException ('$LOGIN_UNKNOWN_USER');
      else if (!$user->verifyPassword($password))
        throw new AuthenticationException ('$LOGIN_WRONG_PASSWORD');
      else if (!$user->activeField())
        throw new AuthenticationException ('$LOGIN_DISABLED');
      else {
        $user->onLogin();
        $this->session->setUser($user);
      }
    }
  }

  function __construct(SessionInterface $session, UserInterface $user, RedirectionInterface $redirection)
  {
    $this->session = $session;
    $this->user = $user;
    $this->redirection = $redirection;
  }

  function onSubmit($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest($request);
    $session = $this->session;

    if (isset($data['lang']))
      $session->setLang($data['lang']);

    $this->doLogin($data['email'], $data['password']);
    return $redirect->intended($request->getAttribute('baseUri'));
  }


  function forgotPassword($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest($request);
    $session = $this->session;

    try {
      if (empty($data['email'])) {
        throw new AuthenticationException(AuthenticationException::MISSING_EMAIL);
      }
      //return $redirect->intended($request->getAttribute('baseUri'));
    } catch (AuthenticationException $e) {
      $session->flashInput($data);
      $session->flashMessage($e->getMessage());
      $session->reflashPreviousUrl();
      return $redirect->refresh();
    }

    //gerar link de recup senha com token guardado na tabela de users

    $sSubject = 'Recuperação de Senha';
    $sBody = <<<HTML
<p>Recebemos um pedido para recuperar a sua senha, por favor clique no link em baixo.</p>
<p>
      <a>Recuperar Password</a>
</p>
HTML;

    new SendEmail($sSubject, $sBody, $data['email']);
  }
}

