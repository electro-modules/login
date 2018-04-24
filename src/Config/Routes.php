<?php
namespace Electro\Plugins\Login\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Routes implements RequestHandlerInterface
{
  /** @var AuthenticationSettings */
  private $authenticationSettings;
  /** @var LoginSettings */
  private $loginSettings;
  /** @var RouterInterface */
  private $router;

  public function __construct (AuthenticationSettings $authenticationSettings, RouterInterface $router,
                               LoginSettings $loginSettings)
  {
    $this->router                 = $router;
    $this->authenticationSettings = $authenticationSettings;
    $this->loginSettings          = $loginSettings;
  }

  /**
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   * @throws \Electro\Exceptions\Fault
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $auth = $this->authenticationSettings;
    $st   = $this->loginSettings;

    return $this->router
      ->add ([
        $auth->urlPrefix () . '...' => [
          $auth->loginFormUrl () => page ('login/login.html',
            controller ([$st->loginController, 'onSubmit'])),

          $st->routeRegister => when ($st->routeRegisterOnOff,
            page ('register/register.html',
              controller ([$st->registerController, 'onSubmitRegister']))),

          $st->routeResetPassword => when ($st->routeResetPasswordOnOff,
            page ('resetPassword/resetPassword.html',
              controller ([$st->loginController, 'forgotPassword']))),

          $st->routeResetPasswordToken => when ($st->routeResetPasswordOnOff, [
            injectableHandler ([$this->loginSettings->resetPasswordController, 'validateToken']),
            page ('resetPassword/newPassword.html',
              controller ([$this->loginSettings->resetPasswordController, 'resetPassword'])),
          ]),

          $st->routeActivateUserToken => when ($st->routeActivateUserOnOff, [
            injectableHandler ([$this->loginSettings->resetPasswordController, 'validateToken']),
            page ('activateUser/activateUser.html'),
          ]),

          $st->routeAdminActivateUserToken => when ($st->routeAdminActivateUserOnOff, [
            injectableHandler ([$this->loginSettings->resetPasswordController, 'validateToken']),
            page ('activateUser/adminActivateUser.html'),
          ]),

        ],
      ])
      ->__invoke ($request, $response, $next);
  }

}
