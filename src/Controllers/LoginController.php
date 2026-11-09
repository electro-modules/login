<?php

namespace Electro\Plugins\Login\Controllers;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use Electro\Sessions\Config\SessionSettings;
use HansOtt\PSR7Cookies\SetCookie;
use Lurker\Exception\RuntimeException;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class LoginController
{
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var LoginSettings
   */
  private $loginSettings;
  /** @var PHPMailer */
  private $mailer;
  /**
   * @var NavigationInterface
   */
  private $navigation;
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /**
   * @var SessionSettings
   */
  private $sessionSettings;
  /** @var UserInterface */
  private $user;

	function __construct (SessionInterface $session, UserInterface $user, RedirectionInterface $redirection,
                        PHPMailer $mailer, ConnectionsInterface $connections, KernelSettings $kernelSettings,
                        NavigationInterface $navigation, LoginSettings $loginSettings, SessionSettings $sessionSettings)
  {
    $this->session         = $session;
    $this->user            = $user;
    $this->redirection     = $redirection;
    $this->mailer          = $mailer;
    $this->kernelSettings  = $kernelSettings;
    $this->navigation      = $navigation;
    $this->loginSettings   = $loginSettings;
    $this->sessionSettings = $sessionSettings;
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
      else if (!$user->active)
        throw new AuthenticationException ('$LOGIN_NOTACTIVE');
      else if (!$user->enabled)
        throw new AuthenticationException ('$LOGIN_DISABLED');
      else {
        $user->onLogin ();
        $this->session->setUser ($user);
      }
    }
  }

  function forgotPassword ($data, ServerRequestInterface $request)
  {
    $redirect = $this->redirection->setRequest ($request);
    $response = $redirect->to ($this->navigation['login']->url ());
    $settings = $this->sessionSettings;
    $cookieName = $settings->sessionName . "_" . $settings->rememberMeTokenName;

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
      if ($this->user->active == 0) throw new AuthenticationException('$LOGIN_DISABLED', FlashType::ERROR);
      $token = bin2hex (openssl_random_pseudo_bytes (16));
      $this->user->mergeFields (['token' => $token]);

      $cookies = $request->getCookieParams ();

      if (array_key_exists ($cookieName, $cookies)) {
        $cookie   =
          SetCookie::thatStaysForever ($cookieName,
            $this->user->token,
            $request->getAttribute ('baseUri'));
        $response = $cookie->addToResponse ($response);
      }

      $this->user->submit ();
      $r = $this->sendResetPasswordEmail (get ($data, 'email'), $token);
      if ($r) return $r;
      return $response;
    }
  }

  /**
   * @param array                  $data
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @return ResponseInterface
   * @throws AuthenticationException
   */
  function onSubmit ($data, ServerRequestInterface $request, ResponseInterface $response)
  {
    try {
      $redirect      = $this->redirection->setRequest ($request);
      $session       = $this->session;
      $loginSettings = $this->loginSettings;
      $session->setLang (get ($data, 'lang'));
      $settings   = $this->sessionSettings;
      $cookieName = $settings->sessionName . "_" . $settings->rememberMeTokenName;

      if ($loginSettings->varUserOrEmailOnLogin) $usernameEmail = "email";
      else $usernameEmail = "username";

      $this->doLogin (get ($data, $usernameEmail), get ($data, 'password'));

      $response = $redirect->intended ($request->getAttribute ('baseUri') . $this->loginSettings->urlRedirectAfterLogin);

      if (get ($data, 'remember')) {
        $token = bin2hex (openssl_random_pseudo_bytes (16));
        $this->user->mergeFields (['token' => $token]);
        $this->user->submit ();
        $cookie =
          SetCookie::thatStaysForever ($cookieName,
            $this->user->token,
            $request->getAttribute ('baseUri'));
        return $cookie->addToResponse ($response);
      }
      return $response;
    }
    catch (AuthenticationException $e) {
      $this->session->reflashPreviousUrl ();
      throw $e;
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

    if ((env ('EMAIL_SENDER_ADDR') != '') && (env ('EMAIL_SENDER_NAME') != '') && (env ('EMAIL_SMTP_HOST') != '') &&
        (env ('EMAIL_SMTP_USERNAME') != '') && (env ('EMAIL_SMTP_PASSWORD') != '')
    ) {
      try {
        $mailer = $this->mailer;
        $mailer->clearAllRecipients ();
        $mailer->isSMTP ();
        $mailer->Host       = env ('EMAIL_SMTP_HOST');
        $mailer->SMTPAuth   = true;
        $mailer->Username   = env ('EMAIL_SMTP_USERNAME');
        $mailer->Password   = env ('EMAIL_SMTP_PASSWORD');
        $mailer->Port       = (int)(env ('EMAIL_SMTP_PORT') ?: 587);
        $mailer->SMTPSecure = env ('EMAIL_SMTP_SECURE') ?: PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->CharSet    = PHPMailer::CHARSET_UTF8;
        $mailer->setFrom (env ('EMAIL_SENDER_ADDR'), env ('EMAIL_SENDER_NAME'));
        $mailer->addAddress ($emailTo);
        $mailer->isHTML (true);
        $mailer->Subject = $sSubject;
        $mailer->Body    = $sBody;
        $result = $mailer->send ();
      }
      catch (PHPMailerException $e) {
        $result = false;
      }

      if ($result) {
        return $this->session->flashMessage ('$RECOVERPASS_SUCCESS_EMAIL', FlashType::SUCCESS);
      }
      throw new AuthenticationException('$RECOVERPASS_ERROR_EMAIL', FlashType::ERROR);
    }
    else throw new RuntimeException('$ERROR_MAIL_SENDER_ENV');
  }
}

