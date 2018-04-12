<?php

namespace Electro\Plugins\Login\Controllers\Register;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
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

  function onSubmit($data, ServerRequestInterface $request, ResponseInterface $response)
  {
    $redirect = $this->redirection->setRequest($request);

    $session = $this->session;

    if (isset($data['lang']))
      $session->setLang($data['lang']);

    $r = $this->validateData($data, $request);

    if ($r) return $r;

    else {
      $newPassword = password_hash($data['password'], PASSWORD_BCRYPT);
      $now = date("Y-m-d h:i:s");

      $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$data['username']])->fetchObject();
      if ($user) throw new AuthenticationException('$REGISTER_ERROR_EMAIL_NOTUNIQUE', FlashType::ERROR);

      else {
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        $this->db->exec('INSERT INTO ' . $this->loginSettings->usersTableName . ' (created_at,updated_at,email,password,realName, registrationDate,role,active,rememberToken) VALUES(?,?,?,?,?,?,?,?,?);', [$now, $now, $data['username'], $newPassword, $data['realName'], $now, UserInterface::USER_ROLE_STANDARD, 0, $token]);

        $return = $this->sendActivationEmail($data['username'], $token);
        if ($return) return $return;

        return redirectTo('login');
      }
    }
  }

  private function validateData($data)
  {
    if (empty($data['realName']))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty($data['username']))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty($data['password']))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if (empty($data['password2']))
      throw new AuthenticationException('$REGISTER_MISSING_INFO', FlashType::WARNING);
    else if ($data['password'] != $data['password2'])
      throw new AuthenticationException('$REGISTER_ERROR_PASS', FlashType::WARNING);
    else if (!filter_var($data['username'], FILTER_VALIDATE_EMAIL))
      throw new AuthenticationException('$REGISTER_ERROR_VALIDATE_EMAIL', FlashType::WARNING);
  }

  private function sendActivationEmail($emailTo, $token){
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
    }
    else
    {
      $this->db->exec('DELETE FROM ' . $this->loginSettings->usersTableName . ' WHERE email = ?', [$data['username']]);
      throw new AuthenticationException('$ACTIVATEUSER_EMAILACTIVATION_ERROR', FlashType::ERROR);
    }
  }
}

