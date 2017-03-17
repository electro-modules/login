<?php
namespace Electro\Plugins\Login\ViewModels\Login;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interop\ViewModel;
use Electro\Plugins\IlluminateDatabase\DatabaseAPI;
use Psr\Http\Message\ServerRequestInterface;

class Login extends ViewModel
{
  /**
   * @var RedirectionInterface
   */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;

  public function __construct (DatabaseAPI $db, SessionInterface $session, UserInterface $user,
                               RedirectionInterface $redirection)
  {
    parent::__construct ();
    //$db is unused om purpose, do not remove.
    $this->session     = $session;
    $this->user        = $user;
    $this->redirection = $redirection;
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
      throw new AuthenticationException (AuthenticationException::MISSING_INFO);
    else {
      $user = $this->user;
      if (!$user->findByName ($username))
        throw new AuthenticationException (AuthenticationException::UNKNOWN_USER);
      else if (!$user->verifyPassword ($password))
        throw new AuthenticationException (AuthenticationException::WRONG_PASSWORD);
      else if (!$user->activeField ())
        throw new AuthenticationException (AuthenticationException::DISABLED);
      else {
        try {
          $user->onLogin ();
          $this->session->setUser ($user);
        }
        catch (\Exception $e) {
          throw new AuthenticationException($e->getMessage ());
        }
      }
    }
  }

  public function init ()
  {
    $session = $this->session;
    $session->reflashPreviousUrl ();
    $this->set ([
      'username' => $session->getOldInput ('username'),
      'password' => $session->getOldInput ('password'),
      'lang'     => $session->getOldInput ('lang'),
    ]);
  }

  public function onSubmit ($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest ($request);
    $session  = $this->session;

    if (isset($data['lang']))
      $session->setLang ($data['lang']);

    try {
      $this->doLogin ($data['username'], $data['password']);
      return $redirect->intended ($request->getAttribute ('baseUri'));
    }
    catch (AuthenticationException $e) {
      $session->flashInput ($data);
      $session->flashMessage ($e->getMessage ());
      return $redirect->refresh ();
    }
  }

}
