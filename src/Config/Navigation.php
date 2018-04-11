<?php

namespace Electro\Plugins\Login\Config;

use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationProviderInterface;

class Navigation implements NavigationProviderInterface
{
  function defineNavigation (NavigationInterface $nav)
  {
    $nav->add ([
      'login' => $nav
        ->group ()
        ->links ([
          'login' => $nav
            ->link ()
            ->id ('login')
            ->title ('$LOGIN_PROMPT'),

          'resetpassword' => $nav
            ->link ()
            ->id ('forgotPassword')
            ->title ('$RECOVERPASS'),

          'resetpassword/@token' => $nav
            ->link ()
            ->id ('resetPassword')
            ->title ('$RECOVERPASS'),
        ]),

    ]);
  }

}
