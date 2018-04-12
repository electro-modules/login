<?php

namespace Electro\Plugins\Login\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationProviderInterface;

class Navigation implements NavigationProviderInterface
{
  /**
   * @var LoginSettings
   */
  private $loginSettings;
  /**
   * @var AuthenticationSettings
   */
  private $authenticationSettings;


  /**
   * Navigation constructor.
   * @param LoginSettings $loginSettings
   * @param AuthenticationSettings $authenticationSettings
   */
  public function __construct(LoginSettings $loginSettings, AuthenticationSettings $authenticationSettings)
  {
    $this->loginSettings = $loginSettings;
    $this->authenticationSettings = $authenticationSettings;
  }

  function defineNavigation (NavigationInterface $nav)
  {
    $loginSettings = $this->loginSettings;
    $auth = $this->authenticationSettings;

    $nav->add ([
      $auth->urlPrefix() => $nav
        ->group ()
        ->links ([
          $auth->loginFormUrl() => $nav
            ->link ()
            ->id ('login')
            ->title ('$LOGIN_PROMPT'),

          $loginSettings->routeResetPassword => $nav
            ->link ()
            ->id ('forgotPassword')
            ->title ('$RECOVERPASS'),

          $loginSettings->routeResetPasswordToken => $nav
            ->link ()
            ->id ('resetPassword')
            ->title ('$RECOVERPASS'),

          $loginSettings->routeRegister => $nav
            ->link ()
            ->id ('registerUser')
            ->title ('$LOGIN_REGISTER_USER'),

          $loginSettings->routeActivateUserToken => $nav
            ->link ()
            ->id ('activateUser')
            ->title ('$ACTIVATEUSER'),

          $loginSettings->routeAdminActivateUserToken => $nav
            ->link ()
            ->id ('adminActivateUser')
            ->title ('$ACTIVATEUSER'),
        ]),

    ]);
  }

}
