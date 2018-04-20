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

  /*
   * Variable names
   */

  /**
   * @var boolean Defines if it is username or email, to log in. If it is "true", email is being used to login, if it
   *      is "false", username is being used to login
   */
  public $varUserOrEmailOnLogin = false;

  public $masterLayout = 'layouts/master.html';
}
