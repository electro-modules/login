<?php
namespace Selenia\Plugins\Login\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Core\Profiles\WebProfile;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Localization\Config\LocalizationSettings;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Plugins\Login\Controllers\Login;
use const Electro\Core\Assembly\Services\CONFIGURE;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

class LoginModule implements ModuleInterface, RequestHandlerInterface
{
  /** @var AuthenticationSettings */
  private $authenticationSettings;
  /** @var RouterInterface */
  private $router;

  public function __construct (AuthenticationSettings $authenticationSettings, RouterInterface $router)
  {
    $this->router                 = $router;
    $this->authenticationSettings = $authenticationSettings;
  }

  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    if ($bootstrapper->profile instanceof WebProfile)
      $bootstrapper
        //
        ->on (REGISTER_SERVICES,
          function (InjectorInterface $injector) {
            $injector->share (LoginSettings::class);
          })
        //
        ->on (CONFIGURE,
          function (LocalizationSettings $localizationSettings, ViewEngineSettings $viewEngineSettings,
                    ApplicationRouterInterface $applicationRouter
          ) use ($moduleInfo) {
            $localizationSettings->registerTranslations ($moduleInfo);
            $viewEngineSettings->registerViews ($moduleInfo);
            $applicationRouter->add (self::class, 'login', 'platform');
          });
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $auth = $this->authenticationSettings;
    return $this->router
      ->set ([
        $auth->urlPrefix () . '...' => [
          $auth->loginFormUrl () => Login::class,
          $auth->logoutUrl ()    => controller ([Login::class, 'logout']),
        ],
      ])
      ->__invoke ($request, $response, $next);
  }

}
