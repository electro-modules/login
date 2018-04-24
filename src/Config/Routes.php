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
    $auth          = $this->authenticationSettings;
    $loginSettings = $this->loginSettings;

    return $this->router
      ->add ([
        $auth->urlPrefix () . '...' => [
          $auth->loginFormUrl () => page ('login/login.html',
            controller ([$this->loginSettings->loginController, 'onSubmit'])),

          $loginSettings->routeRegister => when ($loginSettings->routeRegisterOnOff,
            page ('register/register.html',
              controller ([$this->loginSettings->registerController, 'onSubmitRegister']))),

          $loginSettings->routeResetPassword => when ($loginSettings->routeResetPasswordOnOff,
            page ('resetPassword/resetPassword.html',
              controller ([$this->loginSettings->loginController, 'forgotPassword']))),
        ],
      ])
      ->__invoke ($request, $response, $next);
  }

}
