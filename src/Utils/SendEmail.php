<?php

namespace Electro\Plugins\Login\Utils;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use function env;

class SendEmail
{
  private $body;
  private $subject;
  private $to;

  function __construct ($subject, $body, $to)
  {
    $this->subject = $subject;
    $this->body    = $body;
    $this->to      = $to;

    $this->run ();
  }

  private function run ()
  {
    $oMessage = Swift_Message::newInstance ($this->subject);

    $oMessage->setFrom ([env ('EMAIL_SENDER_ADDR') => env ('EMAIL_SENDER_NAME')])
             ->setTo ($this->to)
             ->setBody ($this->body)
             ->setContentType ('text/html');

    $transport = Swift_SmtpTransport::newInstance(env ('EMAIL_SMTP_HOST'));
    $transport->setUsername(env ('EMAIL_SMTP_USERNAME'))
        ->setPassword(env ('EMAIL_SMTP_PASSWORD'));

    $oMailer = Swift_Mailer::newInstance($transport);
    $oMailer->send ($oMessage);
  }
}
