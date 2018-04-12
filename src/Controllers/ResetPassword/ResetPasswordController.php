<?php

namespace Electro\Plugins\Login\Controllers\ResetPassword;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Config\LoginSettings;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResetPasswordController
{
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;

  private $db;
  /**
   * @var LoginSettings
   */
  private $loginSettings;

  function __construct(SessionInterface $session, UserInterface $user, RedirectionInterface $redirection, \Swift_Mailer $mailer, ConnectionsInterface $connections, LoginSettings $loginSettings)
  {
    $this->session = $session;
    $this->user = $user;
    $this->redirection = $redirection;
    $this->mailer = $mailer;
    $this->db = $connections->get()->getPdo();
    $this->loginSettings = $loginSettings;
  }

  /* Verificar Token do url, se é valido ou não, se não for faz redirect para a página de recuperação de palavra-passe base*/
  public static function validateToken(ServerRequestInterface $request, $response, $next, ConnectionsInterface $connections, LoginSettings $loginSettings)
  {
    $db = $connections->get()->getPdo();

    $user = $db->select('SELECT * FROM ' . $loginSettings->usersTableName . ' WHERE rememberToken = ?', [$request->getAttribute('@token')])->fetchObject();

    if ($user) {
      return $next();
    }
    return redirectTo('login');
  }

  function resetPassword($data, $token, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest($request);
    $password = $data['password'];
    $password2 = $data['password2'];

    if (empty($password) || empty($password2))
      throw new AuthenticationException('$RESETPASSWORD_MISSINGINFO', FlashType::ERROR);

    if ($password == $password2) {
      $newPassword = password_hash($password, PASSWORD_BCRYPT);

      $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE rememberToken = ?', [$token])->fetchObject();
      if ($user) {
        $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET password = ? WHERE id = ?;', [$newPassword, $user->id]);
        $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET rememberToken = ? WHERE id = ?;', ["", $user->id]);
        $this->session->flashMessage('$RESETPASSWORD_SUCCESS_PASS', FlashType::SUCCESS);
        return redirectTo('login');
      }

    } else
      throw new AuthenticationException('$RESETPASSWORD_ERROR_PASS', FlashType::ERROR);
  }
}

