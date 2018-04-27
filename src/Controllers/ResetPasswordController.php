<?php

namespace Electro\Plugins\Login\Controllers;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\Login\Config\LoginSettings;
use Electro\Sessions\Config\SessionSettings;
use GuzzleHttp\Psr7\ServerRequest;
use HansOtt\PSR7Cookies\RequestCookies;
use HansOtt\PSR7Cookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResetPasswordController
{
  /**
   * @var LoginSettings
   */
  private $loginSettings;
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
                        \Swift_Mailer $mailer, LoginSettings $loginSettings,
                        NavigationInterface $navigation, SessionSettings $sessionSettings)
  {
    $this->session         = $session;
    $this->user            = $user;
    $this->redirection     = $redirection;
    $this->mailer          = $mailer;
    $this->loginSettings   = $loginSettings;
    $this->navigation      = $navigation;
    $this->sessionSettings = $sessionSettings;
  }

  /* Verificar Token do url, se é valido ou não, se não for faz redirect para a página de recuperação de palavra-passe base*/
  public static function validateToken (ServerRequestInterface $request, $response, $next, UserInterface $user)
  {

    if ($user->findByToken ($request->getAttribute ('@token'))) {
      if ($user->enabled == 1) return $next();
    }
    return redirectTo ('login');
  }

  function resetPassword ($data, $token, ServerRequestInterface $request, ResponseInterface $response)
  {
    $redirect = $this->redirection->setRequest ($request);
    $settings = $this->sessionSettings;

    $password  = get ($data, 'password');
    $password2 = get ($data, 'password2');

    $sessionName = $settings->sessionName;
    $rememberMeTokenName = $settings->rememberMeTokenName;
    $cookieName = $sessionName . "_" . $rememberMeTokenName;

    $response = $redirect->to ($this->navigation['login']->url ());

    if (empty($password) || empty($password2))
      throw new AuthenticationException('$RESETPASSWORD_MISSINGINFO', FlashType::ERROR);

    if ($password == $password2) {
      if ($this->user->findByToken ($token)) {
        $token = bin2hex (openssl_random_pseudo_bytes (16));
        $this->user->mergeFields (['password' => $password, 'token' => $token]);

        if ($this->loginSettings->loginAfterResetPassword) {
          $response = $redirect->intended ($request->getAttribute ('baseUri'));
          $this->session->setUser ($this->user);
        }

        $cookies       = RequestCookies::createFromRequest ($request);

        if ($cookies->has ($cookieName)) {
          $cookie   =
            SetCookie::thatStaysForever ($cookieName,
              $this->user->token,
              $request->getAttribute ('baseUri'));
          $response = $cookie->addToResponse ($response);
        }

        $this->user->submit ();
        $this->session->flashMessage ('$RESETPASSWORD_SUCCESS_PASS', FlashType::SUCCESS);

        return $response;
      }
    }
    else
      throw new AuthenticationException('$RESETPASSWORD_ERROR_PASS', FlashType::ERROR);
  }
}

