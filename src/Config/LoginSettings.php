<?php
namespace Electro\Plugins\Login\Config;

use Electro\Plugins\Login\Controllers\Login\LoginController;

/**
 * Configuration settings for the LoginForms module.
 */
class LoginSettings
{
  /**
   * @var string Additional title displayed on the login form.
   */
  public $title;

  public $controller = [LoginController::class,'onSubmit'];

	/**
	 * @var string Defines which url is used for redirecting after login
	 */
	public $urlRedirectAfterLogin = "/admin";
}
