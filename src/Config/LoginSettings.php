<?php
namespace Electro\Plugins\Login\Config;

use Electro\Plugins\Login\Controllers\LoginController;
use Electro\Plugins\Login\Controllers\RegisterController;
use Electro\Plugins\Login\Controllers\ResetPasswordController;

/**
 * Configuration settings for the LoginForms module.
 */
class LoginSettings
{
  /**
   * @var string Additional title displayed on the login form.
   */
  public $title;

  /**
   * @var LoginController Controller name used in Routes
   */
  public $loginController = LoginController::class;

  /**
   * @var RegisterController Controller name used in Routes
   */
  public $registerController = RegisterController::class;

  /**
   * @var ResetPasswordController Controller name used in Routes
   */
  public $resetPasswordController = ResetPasswordController::class;

  /**
   * @var boolean Activation of account by administrator or not
   *
   */
  public $routeAdminActivateUserOnOff = false;

  /**
   * @var string Admin email that will activate account, If empty do not send email to admin
   */
  public $approvalAdminEmail = '';

  /**
   *
   * @var string Users table name on your database, only in case you use the default user class
   */
  public $usersTableName = "users";

  /**
   * @var boolean Defines register user (functionality) is active or not
   */
  public $routeRegisterOnOff = true;

  /**
   * @var boolean Defines reset password (functionality) is active or not
   */
  public $routeResetPasswordOnOff = true;

  /**
   * @var boolean Defines activation of user (functionality) is active or not
   */
  public $routeActivateUserOnOff = true;

  /**
   * @var boolean Showing rememberMe (keep signed in) checkbox on login page.
   */
  public $rememberMeLoggedIn = true;

  /**
   * @var boolean Login after reseting password or not.
   */
  public $loginAfterResetPassword = true;

  /*
   * URL configuration, Names of routes
   */

  public $routeRegister = "register";

  public $routeResetPassword = "resetpassword";

  public $routeResetPasswordToken = "resetpassword/@token";

  public $routeActivateUserToken = "activateuser/@token";

  public $routeAdminActivateUserToken = "adminactivateuser/@token";

	/**
	 * @var string Defines which url is used for redirecting after login
	 */
	public $urlRedirectAfterLogin = "/admin";

  /**
   * @var boolean Defines which field is used for logging in: true = email, false = username.
   */
  public $varUserOrEmailOnLogin = false;

  /**
   * @var bool When true, the email field is displayed on the user form and on the users' grid.
   *           Note: if you want to use registration, account activation or password reset, or if you set
   *           varUserOrEmailOnLogin to true, you should display the email field, as it is required for the proper
   *           functioning of those features.
   */
  public $displayEmail = true;

  /**
   * @var bool When true, the username field is displayed on the user form and on the users' grid.
   *           Note: if you set varUserOrEmailOnLogin to false, you should display the username field.
   */
  public $displayUsername = true;

  public $masterLayout = 'layouts/master.html';
}
