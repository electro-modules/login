<?php
namespace Selenia\Plugins\Login\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Core\Assembly\Services\ModuleServices;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Interfaces\Http\RouterInterface;
use Selenia\Interfaces\InjectorInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationProviderInterface;
use Selenia\Plugins\Login\Controllers\Login;

class LoginModule implements ModuleInterface, RequestHandlerInterface, NavigationProviderInterface
{
  /** @var RouterInterface */
  private $router;
  /** @var LoginSettings */
  private $settings;

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    return $this->router
      ->set ([
        $this->settings->urlPrefix () . '...' => [
          'login' => Login::class,
        ],
      ])
      ->__invoke ($request, $response, $next);
  }

  function configure (InjectorInterface $injector, ModuleServices $module, LoginSettings $settings,
                      RouterInterface $router)
  {
    $injector->share (LoginSettings::class);
    $this->settings = $settings;
    $this->router   = $router;
    $module
      ->provideTranslations ()
      ->provideViews ()
      ->onPostConfig (function () use ($module) {
        $module
          ->registerNavigation ($this)
          ->registerRouter ($this);
      });
  }

  function defineNavigation (NavigationInterface $navigation)
  {
    $prefix = $this->settings->urlPrefix ();
    $navigation->add ([
      "$prefix/login" => $navigation
        ->link ()
        ->id ('login')
        ->title ('$LOGIN_PROMPT')
        ->visible (N),
    ]);
  }

}
