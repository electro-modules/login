<?php
namespace Selenia\Plugins\Login\Controllers;

use Selenia\Authentication\Exceptions\AuthenticationException;
use Selenia\Http\Components\PageComponent;
use Selenia\Interfaces\SessionInterface;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;

class Login extends PageComponent
{
  public $templateUrl = 'login/login.html';
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;

  public function action_login ($param = null)
  {
    if (isset($this->model['lang']))
      $this->session->setLang ($this->model['lang']);
    $this->doLogin ($this->model['username'], $this->model['password']);
    return $this->redirection->intended ($this->request->getAttribute ('baseUri'));
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

  function inject ()
  {
    return function (DatabaseAPI $db, SessionInterface $session, UserInterface $user) {
      $this->session = $session;
      $this->user    = $user;
      //$db is unused om purpose, do not remove.
    };
  }

  protected function initialize ()
  {
    parent::initialize ();
    $this->session->reflashPreviousUrl ();
  }

  protected function model ()
  {
    $this->modelController->setModel ([
      'username' => '',
      'password' => '',
      'lang'     => null,
    ]);
  }

}
