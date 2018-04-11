<?php

namespace Electro\Plugins\Login\Controllers\ResetPassword;

use Electro\Exceptions\FlashType;
use Electro\Http\Lib\Http;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Services\DefaultUser;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Psr\Http\Message\ResponseInterface;
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

  function __construct(SessionInterface $session, UserInterface $user, RedirectionInterface $redirection, \Swift_Mailer $mailer, ConnectionsInterface $connections)
  {
    $this->session = $session;
    $this->user = $user;
    $this->redirection = $redirection;
    $this->mailer = $mailer;
    $this->db = $connections->get()->getPdo();
  }

  /* Verificar Token do url, se é valido ou não, se não for faz redirect para a página de recuperação de palavra-passe base*/
  public static function validateToken(ServerRequestInterface $request, $response, $next, ConnectionsInterface $connections)
  {
    $db = $connections->get()->getPdo();

    $user = $db->select('SELECT * FROM ' . DefaultUser::usersTableName . ' WHERE rememberToken = ?', [$request->getAttribute('@token')])->fetchObject();

    if ($user) {
      return $next();

    }
    return redirectTo('login');
  }

  function resetPassword($data, $token, ServerRequestInterface $request, ResponseInterface $response)
  {
    $redirect = $this->redirection->setRequest($request);
    $password = $data['password'];
    $password2 = $data['password2'];

    if (empty($password) || empty($password2)) {
      $this->session->flashMessage('$RESETPASSWORD_MISSINGINFO', FlashType::ERROR);
      return $redirect->refresh();
    }

    if ($password == $password2) {
      $newPassword = password_hash($password, PASSWORD_BCRYPT);

      $user = $this->db->select('SELECT * FROM ' . DefaultUser::usersTableName . ' WHERE rememberToken = ?', [$token])->fetchObject();
      if ($user) {
        $this->db->exec('UPDATE ' . DefaultUser::usersTableName . ' SET password = ? WHERE id = ?;', [$newPassword, $user->id]);
        //$this->db->exec('UPDATE ' . DefaultUser::usersTableName . ' SET rememberToken = ? WHERE id = ?;', ["", $user->id]);
        $this->session->flashMessage('$RESETPASSWORD_SUCCESS_PASS', FlashType::SUCCESS);
        return redirectTo('login');
      }

    } else {
      $this->session->flashMessage('$RESETPASSWORD_ERROR_PASS', FlashType::ERROR);
      return $redirect->refresh();
    }
  }
}

