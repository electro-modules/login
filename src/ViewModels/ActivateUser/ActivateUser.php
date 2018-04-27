<?php

namespace Electro\Plugins\Login\ViewModels\ActivateUser;

use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interfaces\UserInterface;
use Electro\Interop\ViewModel;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use Lurker\Exception\RuntimeException;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;
use Swift_Message;

class ActivateUser extends ViewModel
{
  /** @var SessionInterface */
  private $session;
  /**
   * @var LoginSettings
   */
  private $loginSettings;
  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var NavigationInterface
   */
  private $navigation;
  /**
   * @var \Swift_Mailer
   */
  private $mailer;
  /**
   * @var UserInterface
   */
  private $user;

  public function __construct(SessionInterface $session, LoginSettings $loginSettings, KernelSettings $kernelSettings, NavigationInterface $navigation, \Swift_Mailer $mailer, UserInterface $user)
  {
    parent::__construct();
    $this->session = $session;
    $this->loginSettings = $loginSettings;
    $this->kernelSettings = $kernelSettings;
    $this->navigation = $navigation;
    $this->mailer = $mailer;
    $this->user = $user;
  }

  public function init()
  {
    $adminAprovation = $this->loginSettings->routeAdminActivateUserOnOff;
    $token = $this['props']['token'];

    if ($adminAprovation == false) {
      if ($this->user->findByToken($token)) {
        $this->user->mergeFields (['active' => 1]);
        $this->user->submit();
      }
      $this->set([
        'activateUser' => '$ACTIVATEUSER_SUCCESS']);
    } else {

      if ($this->user->findByToken($token)) {
        $r = $this->sendActivationEmailToAdmin($this->loginSettings->approvalAdminEmail, $token);

        if ($r) return $r;

        $this->set([
          'activateUser' => '$ACTIVATEUSER_PROMPT_WITHADMIN']);
      }
    }
  }

  private function sendActivationEmailToAdmin($emailTo, $token)
  {
    $url = $this->kernelSettings->baseUrl;
    $url2 = $this->navigation['adminActivateUser'];

    $this->user->findByToken($token);
    $realName = $this->user->realName;
    $email = $this->user->email;

    $sSubject = 'Ativação de Nova Conta de Utilizador';
    $sBody = <<<HTML
<p>Foi registado um novo utilizador com os seguintes dados:</p>
<p>Nome real: $realName</p>
<p>Email: $email</p>
</p>
<br>
<p>Para ativar o novo utilizador, clique no link abaixo:</p>
<p>
      <a href="$url/$url2">Ativar utilizador</a>
</p>
HTML;

    $oMessage = Swift_Message::newInstance($sSubject, $sBody);

    if ((env ('EMAIL_SENDER_ADDR') != '') && (env ('EMAIL_SENDER_NAME') != '') && (env ('EMAIL_SMTP_HOST') != '') &&
        (env ('EMAIL_SMTP_USERNAME') != '') && (env ('EMAIL_SMTP_PASSWORD') != '')
    ){
      $oMessage->setFrom([env('EMAIL_SENDER_ADDR') => env('EMAIL_SENDER_NAME')])
               ->setTo($emailTo)
               ->setBody($sBody)
               ->setContentType('text/html');

      $result = $this->mailer->send($oMessage);
    }
    else throw new RuntimeException('$ERROR_MAIL_SENDER_ENV');
  }
}
