<?php
namespace Selenia\Plugins\Login\Config;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Core\Assembly\Services\ModuleServices;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationProviderInterface;
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
      ->registerNavigation ($this)
      ->registerRouter ($this);
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
