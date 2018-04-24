<?php

namespace Electro\Plugins\Login\Config;

use Electro\Authentication\Config\AuthenticationSettings;
use Electro\Authentication\Lib\GenericUser;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Http\Shared\ApplicationRouterInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Localization\Config\LocalizationSettings;
use Electro\Navigation\Config\NavigationSettings;
use Electro\Plugins\Login\Services\User;
use Electro\Profiles\WebProfile;
use Electro\ViewEngine\Config\ViewEngineSettings;

class LoginModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel
      ->onRegisterServices (
        function (InjectorInterface $injector) {
          $injector->share (LoginSettings::class);
        })
      //
      ->onConfigure (
        function (LocalizationSettings $localizationSettings, ViewEngineSettings $viewEngineSettings,
                  ApplicationRouterInterface $applicationRouter, AuthenticationSettings $authSettings,
                  NavigationSettings $navigationSettings
        ) use ($moduleInfo) {
          $localizationSettings->registerTranslations ($moduleInfo);
          $viewEngineSettings->registerViews ($moduleInfo);
          $viewEngineSettings->registerViewModelsNamespace (\Electro\Plugins\Login\ViewModels::class);
          $applicationRouter->add (Routes::class);
          $currentUserModel = $authSettings->userModel ();
          if ($currentUserModel == GenericUser::class)
            $authSettings->userModel (User::class);
          $navigationSettings->registerNavigation (Navigation::class);
        });
  }
}
