<?php
namespace Electro\Plugins\Login\Controllers\Login;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginController
{
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;
  /** @var AuthenticationSettings */
  private $authSettings;

  function __construct (SessionInterface $session, UserInterface $user, RedirectionInterface $redirection, AuthenticationSettings $authenticationSettings)
  {
    $this->session     = $session;
    $this->user        = $user;
    $this->redirection = $redirection;
    $this->authSettings = $authenticationSettings;
  }

  /**
   * Attempts to log in the user with the given credentials.
   *
   * @param string $username
   * @param string $password
   * @throws AuthenticationException If the login fails.
   */
  function doLogin ($username, $password)
  {
    if (empty($username))
      throw new AuthenticationException ('$LOGIN_MISSING_INFO');
    else {
      $user = $this->user;
      if (!$user->findByName ($username))
        throw new AuthenticationException ('$LOGIN_UNKNOWN_USER');
      else if (!$user->verifyPassword ($password))
        throw new AuthenticationException ('$LOGIN_WRONG_PASSWORD');
      else if (!$user->activeField ())
        throw new AuthenticationException ('$LOGIN_DISABLED');
      else {
        $user->onLogin ();
        $this->session->setUser ($user);
      }
    }
  }

  function onSubmit ($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest ($request);
    $session  = $this->session;

    if (isset($data['lang']))
      $session->setLang ($data['lang']);

    $this->doLogin ($data['username'], $data['password']);
      return $redirect->intended ($request->getAttribute ('baseUri') . '/' . $this->authSettings->urlPrefix());
  }

}
