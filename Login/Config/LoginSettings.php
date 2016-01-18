<?php
namespace Selenia\Plugins\Login\Config;

use Selenia\Interfaces\AssignableInterface;
use Selenia\Traits\ConfigurationTrait;

/**
 * Configuration settings for the LoginForms module.
 *
 * @method $this|string  title (string $v = null) Title displayed on the login form. Defaults to the app title
 * @method $this|string  urlPrefix (string $v = null) Relative URL that prefixes all URLs to the login pages
 */
class LoginSettings implements AssignableInterface
{
  use ConfigurationTrait;

  private $title;
  private $urlPrefix = 'login';

}
