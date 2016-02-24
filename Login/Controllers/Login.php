<?php
namespace Selenia\Plugins\Login\Controllers;

use Selenia\Authentication\Exceptions\AuthenticationException;
use Selenia\Http\Components\PageComponent;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\IlluminateDatabase\DatabaseAPI;
use Selenia\Plugins\Login\Config\LoginSettings;

class Login extends PageComponent
{
  public $settings;
  public $templateUrl = 'login/login.html';

  public function action_login ($param = null)
  {
    if ($this->model['lang'])
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
      /** @var UserInterface $user */
      $user = $this->app->createUser ();
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

  protected function initialize ()
  {
    parent::initialize ();
    $this->session->reflashPreviousUrl ();
  }

  function inject ()
  {
    return function (LoginSettings $settings, DatabaseAPI $db) {
      $this->settings = $settings;
      //$db is unused om purpose, do not remove.
    };
  }

  protected function model ()
  {
    return [
      'username' => '',
      'password' => '',
      'lang'     => null,
    ];
  }

}
