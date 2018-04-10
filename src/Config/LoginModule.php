<?php

namespace Electro\Plugins\Login\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Http\RouterInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Navigation\Config\NavigationSettings;
use Electro\Plugins\Login\Controllers\Login\LoginController;
use Electro\Profiles\WebProfile;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Electro\Plugins\Login\Services\DefaultUser;

class LoginModule implements ModuleInterface, RequestHandlerInterface
{
    /** @var AuthenticationSettings */
    private $authenticationSettings;
    /**
     * @var LoginSettings
     */
    private $loginSettings;
    /** @var RouterInterface */
    private $router;

    public function __construct(AuthenticationSettings $authenticationSettings, RouterInterface $router, LoginSettings $loginSettings)
    {
        $this->router = $router;
        $this->authenticationSettings = $authenticationSettings;
        $this->loginSettings = $loginSettings;
    }

    static function getCompatibleProfiles()
    {
        return [WebProfile::class];
    }

    static function startUp(KernelInterface $kernel, ModuleInfo $moduleInfo)
    {
        $kernel
            ->onRegisterServices(
                function (InjectorInterface $injector) {
                    $injector->share(LoginSettings::class);
                })
            //
            ->onConfigure(
                function (LocalizationSettings $localizationSettings, ViewEngineSettings $viewEngineSettings,
                          ApplicationRouterInterface $applicationRouter, AuthenticationSettings $authSettings, NavigationSettings $navigationSettings
                ) use ($moduleInfo) {
                    $localizationSettings->registerTranslations($moduleInfo);
                    $viewEngineSettings->registerViews($moduleInfo);
                    $viewEngineSettings->registerViewModelsNamespace(\Electro\Plugins\Login\ViewModels::class);
                    $applicationRouter->add(self::class, 'login', 'platform');
                    $authSettings->userModel(DefaultUser::class);
                    $navigationSettings->registerNavigation (Navigation::class);
                });
    }

    function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $auth = $this->authenticationSettings;
        return $this->router
            ->set([
                $auth->urlPrefix() . '...' => [
                    $auth->loginFormUrl() => page('login/login.html', controller($this->loginSettings->controller)),
                    'register' => page('register/register.html'),
                    'recoverpassword' => page('recoverpassword/recoverpassword.html', controller([LoginController::class,'forgotPassword']))
                ],
            ])
            ->__invoke($request, $response, $next);
    }

}
