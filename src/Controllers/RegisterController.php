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
use Lurker\Exception\RuntimeException;
use Swift_Mailer;
use Swift_Message;

class RegisterController
{
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var LoginSettings
   */
  private $loginSettings;
  /** @var Swift_Mailer */
  private $mailer;
  /**
   * @var NavigationInterface
   */
  private $navigation;
  /** @var RedirectionInterface */
  private $redirection;
  /** @var SessionInterface */
  private $session;
  /** @var UserInterface */
  private $user;

  function __construct (SessionInterface $session, UserInterface $user, RedirectionInterface $redirection,
                        \Swift_Mailer $mailer, KernelSettings $kernelSettings, NavigationInterface $navigation,
                        LoginSettings $loginSettings)
  {
    $this->session        = $session;
    $this->user           = $user;
    $this->redirection    = $redirection;
    $this->mailer         = $mailer;
    $this->kernelSettings = $kernelSettings;
    $this->navigation     = $navigation;
    $this->loginSettings  = $loginSettings;
  }

  function onSubmitRegister ($data)
  {
    $session = $this->session;
    $session->setLang (get ($data, 'lang'));

    $r = $this->validateData ($data);

    if ($r) return $r;

    $token           = bin2hex (openssl_random_pseudo_bytes (16));
    $data['token']   = $token;
    $data['role']    = UserInterface::USER_ROLE_STANDARD;
    $data['active']  = 0;
    $data['enabled'] = 1;

    $this->user->mergeFields ($data);
    $this->user->submit ();

    $return = $this->sendActivationEmail (get ($data, 'email'), $token);
    if ($return) return $return;

    return redirectTo ('login');
  }

  protected function validateData ($data)
  {
    if (empty(get ($data, 'realName')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (!$this->loginSettings->varUserOrEmailOnLogin &&
             empty(get ($data, 'username'))
    )
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty(get ($data, 'email')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty(get ($data, 'password')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty(get ($data, 'password2')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (get ($data, 'password') != get ($data, 'password2'))
      throw new AuthenticationException('$REGISTER_ERROR_PASS', FlashType::WARNING);
    else if (!filter_var (get ($data, 'email'), FILTER_VALIDATE_EMAIL))
      throw new AuthenticationException('$REGISTER_ERROR_VALIDATE_EMAIL', FlashType::WARNING);
    else if (!$this->loginSettings->varUserOrEmailOnLogin) {
      if ($this->user->findByName (get ($data, 'username'))) {
        if ($this->user->active != 0) throw new AuthenticationException('$REGISTER_ERROR_USERNAME_NOTUNIQUE',
          FlashType::ERROR);
      }
    }
    else if ($this->user->findByEmail (get ($data,
      'email'))
    ) {
      if ($this->user->active != 0) throw new AuthenticationException('$REGISTER_ERROR_EMAIL_NOTUNIQUE',
        FlashType::ERROR);
    }
  }

  private function sendActivationEmail ($emailTo, $token)
  {
    $url  = $this->kernelSettings->baseUrl;
    $url2 = $this->navigation['activateUser'];

    $sSubject = 'Ativação de Nova Conta';
    $sBody    = <<<HTML
<p>Para confirmar a sua conta, por favor clique no link em baixo.</p>
<p>
      <a href="$url/$url2$token">Ativar conta</a>
</p>
HTML;

    $oMessage = new Swift_Message ($sSubject, $sBody);

    if ((env ('EMAIL_SENDER_ADDR') != '') && (env ('EMAIL_SENDER_NAME') != '') && (env ('EMAIL_SMTP_HOST') != '') &&
        (env ('EMAIL_SMTP_USERNAME') != '') && (env ('EMAIL_SMTP_PASSWORD') != '')
    ) {
      $oMessage->setFrom ([env ('EMAIL_SENDER_ADDR') => env ('EMAIL_SENDER_NAME')])
               ->setTo ($emailTo)
               ->setBody ($sBody)
               ->setContentType ('text/html');

      $result = $this->mailer->send ($oMessage);

      if ($result == 1) return $this->session->flashMessage ('$ACTIVATEUSER_EMAILACTIVATION', FlashType::SUCCESS);
      else throw new AuthenticationException('$ACTIVATEUSER_EMAILACTIVATION_ERROR', FlashType::ERROR);
    }
    else throw new RuntimeException('$ERROR_MAIL_SENDER_ENV');
  }
}

