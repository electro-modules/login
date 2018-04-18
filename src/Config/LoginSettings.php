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

  public $loginController = LoginController::class;

  public $registerController = RegisterController::class;

  public $resetPasswordController = ResetPasswordController::class;

  /**
   * @var boolean Activation of account by administrator or not
   */
  public $newAccountsRequireApproval = false;

  /**
   * @var string Admin email that will activate account, If empty do not send email to admin
   */
  public $approvalAdminEmail = '';

  /**
   * @var string Users table name on your database
   */
  public $usersTableName = "users";

  /**
   * @var boolean Defines register user route is active or not
   */
  public $routeRegisterOnOff = true;

  /**
   * @var boolean Defines reset password route is active or not
   */
  public $routeResetPasswordOnOff = true;

  /**
   * @var boolean Defines activation of user route is active or not
   */
  public $routeActivateUserOnOff = true;

  /**
   * @var boolean Defines activation of user by admin (route) is active or not
   */
  public $routeAdminActivateUserOnOff = false;

  /**
   * @var boolean Showing rememberMe (keep signed in) checkbox on login page.
   */
  public $rememberMeLoggedIn = true;

  /**
   * @var boolean Login after reseting password or not.
   */
  public $loginAfterResetPassword = true;

  /*
   * URL configuration
   */

  public $routeRegister = "register";

  public $routeResetPassword = "resetpassword";

  public $routeResetPasswordToken = "resetpassword/@token";

  public $routeActivateUserToken = "activateuser/@token";

  public $routeAdminActivateUserToken = "adminactivateuser/@token";

  /*
   * Variable names
   */

  public $varEmailOnLogin = 'username';

  public $masterLayout = 'layouts/master.html';
}
