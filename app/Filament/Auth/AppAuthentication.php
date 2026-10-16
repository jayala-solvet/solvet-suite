<?php

namespace App\Filament\Auth;

class AppAuthentication extends \Filament\Auth\MultiFactor\App\AppAuthentication
{
    public function getActions(): array
    {
        $actions = parent::getActions();
        foreach ($actions as $action) {
            if ($action->getName() === 'disableAppAuthentication') {
                $action->authorize(fn () => auth()->user()?->role !== 'admin');
            }
        }

        return $actions;
    }
}
