<?php

namespace Electro\Plugins\Login\Controllers\Login;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swift_Mailer;
use Swift_Message;

class LoginController
{
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;
  /** @var Swift_Mailer */
  private $mailer;

  private $db;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var NavigationInterface
   */
  private $navigation;
  /**
   * @var LoginSettings
   */
  private $loginSettings;

  function __construct(SessionInterface $session, UserInterface $user, RedirectionInterface $redirection, \Swift_Mailer $mailer, ConnectionsInterface $connections, KernelSettings $kernelSettings, NavigationInterface $navigation, LoginSettings $loginSettings)
  {
    $this->session = $session;
    $this->user = $user;
    $this->redirection = $redirection;
    $this->mailer = $mailer;
    $this->db = $connections->get()->getPdo();
    $this->kernelSettings = $kernelSettings;
    $this->navigation = $navigation;
    $this->loginSettings = $loginSettings;
  }

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
        throw new AuthenticationException ('$LOGIN_UNKNOWN_USER', FlashType::ERROR);
      else if (!$user->verifyPassword($password))
        throw new AuthenticationException ('$LOGIN_WRONG_PASSWORD', FlashType::ERROR);
      else if (!$user->activeField())
        throw new AuthenticationException ('$LOGIN_DISABLED');
      else {
        $user->onLogin();
        $this->session->setUser($user);
      }
    }
  }

  function onSubmit($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest($request);
    $session = $this->session;
    $loginSettings = $this->loginSettings;

    if (isset($data['lang']))
      $session->setLang($data['lang']);

    $this->doLogin($data[$loginSettings->varEmailOnLogin], $data['password']);
    return $redirect->intended($request->getAttribute('baseUri'));
  }

  function forgotPassword($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest($request);

    if (empty($data['email']))
      throw new AuthenticationException('$RECOVERPASS_MISSINGEMAIL_INPUT');
    else if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) throw new AuthenticationException('$RECOVERPASS_ERROR_VALIDATE_EMAIL', FlashType::ERROR);

    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$data['email']])->fetchObject();
    if (!$user)
      throw new AuthenticationException('$RECOVERPASS_MISSINGEMAIL');
    else {

      if ($user->active == 0) throw new AuthenticationException('$LOGIN_DISABLED', FlashType::ERROR);
      $token = bin2hex(openssl_random_pseudo_bytes(16));
      $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET rememberToken = ? WHERE id = ?;', [$token, $user->id]);
    }

    $url = $this->kernelSettings->baseUrl;
    $url2 = $this->navigation['resetPassword'];

    $sSubject = 'Recuperação de Senha';
    $sBody = <<<HTML
<p>Recebemos um pedido para recuperar a sua senha, por favor clique no link em baixo.</p>
<p>
      <a href="$url/$url2$token">Recuperar Password</a>
</p>
HTML;

    $oMessage = Swift_Message::newInstance($sSubject, $sBody);

    $oMessage->setFrom([env('EMAIL_SENDER_ADDR') => env('EMAIL_SENDER_NAME')])
      ->setTo($data['email'])
      ->setBody($sBody)
      ->setContentType('text/html');

    $result = $this->mailer->send($oMessage);

    if ($result == 1) {
      $this->session->flashMessage('$RECOVERPASS_SUCCESS_EMAIL', FlashType::SUCCESS);
      return $redirect->refresh();
    }
    $this->session->flashMessage('$RECOVERPASS_ERROR_EMAIL', FlashType::ERROR);
    return $redirect->refresh();
  }
}

