<?php
namespace Electro\Plugins\Login\ViewModels\RecoverPassword;

use Electro\Http\Lib\Http;
use Electro\Interfaces\Http\RedirectionInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interop\ViewModel;
use Electro\Plugins\Login\Services\DefaultUser;
use PhpKit\ExtPDO\Interfaces\ConnectionsInterface;

class NewPassword extends ViewModel
{
  /** @var SessionInterface */
  private $session;

  private $db;
  /**
   * @var RedirectionInterface
   */
  private $redirection;

  public function __construct (SessionInterface $session, ConnectionsInterface $connections, RedirectionInterface $redirection)
  {
    parent::__construct ();
    $this->session = $session;
    $this->db = $connections->get()->getPdo();
    $this->redirection = $redirection;
  }

  public function init ()
  {
    $user = $this->db->select('SELECT * FROM ' . DefaultUser::usersTableName . ' WHERE rememberToken = ?', [$this['props']['token']])->fetchObject();

    if ($user){

    }
    else {
      return $this->redirection->to('');

    }
  }

}
