<?php

namespace Electro\Plugins\Login\Controllers;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use GuzzleHttp\Psr7\ServerRequest;
use HansOtt\PSR7Cookies\RequestCookies;
use HansOtt\PSR7Cookies\SetCookie;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Psr\Http\Message\ResponseInterface;
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

  function __construct (SessionInterface $session, UserInterface $user, RedirectionInterface $redirection,
                        \Swift_Mailer $mailer, ConnectionsInterface $connections, KernelSettings $kernelSettings,
                        NavigationInterface $navigation, LoginSettings $loginSettings)
  {
    $this->session        = $session;
    $this->user           = $user;
    $this->redirection    = $redirection;
    $this->mailer         = $mailer;
    $this->kernelSettings = $kernelSettings;
    $this->navigation     = $navigation;
    $this->loginSettings  = $loginSettings;
  }

  /**
   * Attempts to log in the user with the given credentials.
   *
   * @param string $usernameOrEmail
   * @param string $password
   * @throws AuthenticationException If the login fails.
   */
  function doLogin ($usernameOrEmail, $password)
  {
    if (empty($usernameOrEmail))
      throw new AuthenticationException ('$LOGIN_MISSING_INFO');
    else {
      $user = $this->user;

      if ($this->loginSettings->varUserOrEmailOnLogin) {
        if (!$user->findByEmail ($usernameOrEmail))
          throw new AuthenticationException ('$LOGIN_UNKNOWN_USER', FlashType::ERROR);
      }
      else {
        if (!$user->findByName ($usernameOrEmail))
          throw new AuthenticationException ('$LOGIN_UNKNOWN_USER', FlashType::ERROR);
      }

      if (!$user->verifyPassword ($password))
        throw new AuthenticationException ('$LOGIN_WRONG_PASSWORD', FlashType::ERROR);
      else if (!$user->activeField ())
        throw new AuthenticationException ('$LOGIN_NOTACTIVE');
      else if (!$user->enabledField ())
        throw new AuthenticationException ('$LOGIN_DISABLED');
      else {
        $user->onLogin ();
        $this->session->setUser ($user);
      }
    }
  }

  function onSubmit ($data, ServerRequestInterface $request, ResponseInterface $response)
  {
    $redirect      = $this->redirection->setRequest ($request);
    $session       = $this->session;
    $loginSettings = $this->loginSettings;
    $session->setLang (get ($data, 'lang'));

    if ($loginSettings->varUserOrEmailOnLogin) $usernameEmail = "email";
    else $usernameEmail = "username";

    $this->doLogin (get ($data, $usernameEmail), get ($data, 'password'));

    $response = $redirect->intended ($request->getAttribute ('baseUri'));

    if (get ($data, 'remember')) {
      $cookie =
        SetCookie::thatStaysForever ($this->kernelSettings->name . "/" . $this->kernelSettings->rememberMeTokenName,
          $this->user->tokenField (),
          $request->getAttribute ('baseUri'));
      return $cookie->addToResponse ($response);
    }
    return $response;
  }

  function forgotPassword ($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest ($request);
    $response = $redirect->to ($this->navigation['login']->url ());

    if (empty(get ($data, 'email')))
      throw new AuthenticationException('$RECOVERPASS_MISSINGEMAIL_INPUT');
    else if (!filter_var (get ($data, 'email'),
      FILTER_VALIDATE_EMAIL)
    ) throw new AuthenticationException('$RECOVERPASS_ERROR_VALIDATE_EMAIL', FlashType::ERROR);

    if (!$this->user->findByEmail (get ($data,
      'email'))
    ) throw new AuthenticationException('$RECOVERPASS_MISSINGEMAIL');
    else {
      $this->user->findByEmail (get ($data, 'email'));
      if ($this->user->activeField () == 0) throw new AuthenticationException('$LOGIN_DISABLED', FlashType::ERROR);
      $token = bin2hex (openssl_random_pseudo_bytes (16));
      $this->user->tokenField ($token);

      $serverRequest = ServerRequest::fromGlobals ();
      $cookies       = RequestCookies::createFromRequest ($serverRequest);

      if ($cookies->has ($this->kernelSettings->name . "/" . $this->kernelSettings->rememberMeTokenName)) {
        $cookie   =
          SetCookie::thatStaysForever ($this->kernelSettings->name . "/" . $this->kernelSettings->rememberMeTokenName,
            $this->user->tokenField (),
            $request->getAttribute ('baseUri'));
        $response = $cookie->addToResponse ($response);
      }

      $this->user->submit ();
      $r = $this->sendResetPasswordEmail (get ($data, 'email'), $token);
      if ($r) return $r;
      return $response;
    }
  }

  private function sendResetPasswordEmail ($emailTo, $token)
  {
    $url  = $this->kernelSettings->baseUrl;
    $url2 = $this->navigation['resetPassword'];

    $sSubject = 'Recuperação de Senha';
    $sBody    = <<<HTML
<p>Recebemos um pedido para recuperar a sua senha, por favor clique no link em baixo.</p>
<p>
      <a href="$url/$url2$token">Recuperar Password</a>
</p>
HTML;

    $oMessage = Swift_Message::newInstance ($sSubject, $sBody);

    $oMessage->setFrom ([env ('EMAIL_SENDER_ADDR') => env ('EMAIL_SENDER_NAME')])
             ->setTo ($emailTo)
             ->setBody ($sBody)
             ->setContentType ('text/html');

    $result = $this->mailer->send ($oMessage);

    if ($result == 1) {
      return $this->session->flashMessage ('$RECOVERPASS_SUCCESS_EMAIL', FlashType::SUCCESS);
    }
    throw new AuthenticationException('$RECOVERPASS_ERROR_EMAIL', FlashType::ERROR);
  }
}

