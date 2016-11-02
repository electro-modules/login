<?php
namespace Selenia\Plugins\Login\Config;

use Electro\Interfaces\AssignableInterface;
use Electro\Traits\ConfigurationTrait;

/**
 * Configuration settings for the LoginForms module.
 *
 * @method $this|string title (string $v = null) Title displayed on the login form. Defaults to the app title
 */
class LoginSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $title;

}
