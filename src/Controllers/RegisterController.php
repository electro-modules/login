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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swift_Mailer;
use Swift_Message;

class RegisterController
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

  function __construct(SessionInterface $session, UserInterface $user, RedirectionInterface $redirection, \Swift_Mailer $mailer, KernelSettings $kernelSettings, NavigationInterface $navigation, LoginSettings $loginSettings)
  {
    $this->session = $session;
    $this->user = $user;
    $this->redirection = $redirection;
    $this->mailer = $mailer;
    $this->kernelSettings = $kernelSettings;
    $this->navigation = $navigation;
    $this->loginSettings = $loginSettings;
  }

  function onSubmit($data, ServerRequestInterface $request, ResponseInterface $response)
  {
    $session = $this->session;
    $session->setLang(get($data, 'lang'));

    $r = $this->validateData($data);

    if ($r) return $r;

    $token = bin2hex(openssl_random_pseudo_bytes(16));
    $data['token'] = $token;

    $this->user->setRecord($data);
    $this->user->submit();

    $return = $this->sendActivationEmail(get($data, 'email'), $token);
    if ($return) return $return;

    return redirectTo('login');
  }

  protected function validateData($data)
  {
    if (empty(get($data, 'realName')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty(get($data, 'email')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty(get($data, 'password')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty(get($data, 'password2')))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (get($data, 'password') != get($data, 'password2'))
      throw new AuthenticationException('$REGISTER_ERROR_PASS', FlashType::WARNING);
    else if (!filter_var(get($data, 'email'), FILTER_VALIDATE_EMAIL))
      throw new AuthenticationException('$REGISTER_ERROR_VALIDATE_EMAIL', FlashType::WARNING);
    else if ($this->user->findByEmail(get($data, 'email'))) throw new AuthenticationException('$REGISTER_ERROR_EMAIL_NOTUNIQUE', FlashType::ERROR);
  }

  private function sendActivationEmail($emailTo, $token)
  {
    $url = $this->kernelSettings->baseUrl;
    $url2 = $this->navigation['activateUser'];

    $sSubject = 'Ativação de Nova Conta';
    $sBody = <<<HTML
<p>Para confirmar a sua conta, por favor clique no link em baixo.</p>
<p>
      <a href="$url/$url2$token">Ativar conta</a>
</p>
HTML;

    $oMessage = Swift_Message::newInstance($sSubject, $sBody);

    $oMessage->setFrom([env('EMAIL_SENDER_ADDR') => env('EMAIL_SENDER_NAME')])
      ->setTo($emailTo)
      ->setBody($sBody)
      ->setContentType('text/html');

    $result = $this->mailer->send($oMessage);

    if ($result == 1) {
      return $this->session->flashMessage('$ACTIVATEUSER_EMAILACTIVATION', FlashType::SUCCESS);
    } else {
      $this->user->findByEmail($emailTo);
      $this->user->remove();
      throw new AuthenticationException('$ACTIVATEUSER_EMAILACTIVATION_ERROR', FlashType::ERROR);
    }
  }
}

