<?php

namespace Electro\Plugins\Login\ViewModels\ActivateUser;

use Electro\Authentication\Exceptions\AuthenticationException;
use Electro\Exceptions\FlashType;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interop\ViewModel;
use Electro\Kernel\Config\KernelSettings;
use Electro\Plugins\Login\Config\LoginSettings;
use Electro\Plugins\Login\Controllers\Register\RegisterController;
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
   * @var \Swift_Mailer
   */
  private $mailer;

  public function __construct(SessionInterface $session, LoginSettings $loginSettings, ConnectionsInterface $connections, KernelSettings $kernelSettings, NavigationInterface $navigation,  \Swift_Mailer $mailer)
  {
    parent::__construct();
    $this->session = $session;
    $this->loginSettings = $loginSettings;
    $this->db = $connections->get()->getPdo();
    $this->kernelSettings = $kernelSettings;
    $this->navigation = $navigation;
    $this->mailer = $mailer;
  }

  public function init()
  {
    $adminAprovation = $this->loginSettings->newAccountsRequireApproval;
    $token = $this['props']['token'];

    $user = $this->db->select('SELECT * FROM ' . $this->loginSettings->usersTableName . ' WHERE rememberToken = ?', [$token])->fetchObject();

    if ($adminAprovation == false) {
      if ($user) {
        $this->db->exec('UPDATE ' . $this->loginSettings->usersTableName . ' SET active= ?, rememberToken = ? WHERE rememberToken = ?', [1, "", $token]);
      }
      $this->set([
        'activateUser' => '$ACTIVATEUSER_SUCCESS']);
    } else {
      if ($user) {
        $r = $this->sendActivationEmailToAdmin($this->loginSettings->approvalAdminEmail, $token, $user);

        if ($r) return $r;

        $this->set([
          'activateUser' => '$ACTIVATEUSER_PROMPT_WITHADMIN']);
      }
    }
  }

  private function sendActivationEmailToAdmin($emailTo, $token, $user)
  {
    $url = $this->kernelSettings->baseUrl;
    $url2 = $this->navigation['adminActivateUser'];

    $sSubject = 'Ativação de Nova Conta de Utilizador';
    $sBody = <<<HTML
<p>Foi registado um novo utilizador com os seguintes dados:</p>
<p>Nome real: $user->realName</p>
<p>Nome real: $user->email</p>
<br>
<p>Para ativar o novo utilizador, clique no link abaixo:</p>
<p>
      <a href="$url/$url2">Ativar utilizador</a>
</p>
HTML;

    $oMessage = Swift_Message::newInstance($sSubject, $sBody);

    $oMessage->setFrom([env('EMAIL_SENDER_ADDR') => env('EMAIL_SENDER_NAME')])
      ->setTo($emailTo)
      ->setBody($sBody)
      ->setContentType('text/html');

    $result = $this->mailer->send($oMessage);
  }


}
